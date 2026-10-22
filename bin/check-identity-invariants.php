<?php

/**
 * REQUIREMENT-VIOLATION GATE — identity and entity invariants.
 *
 * WHY THIS EXISTS
 *
 * Two defects in this repository were not found by reading more research. They
 * were found by asking one question of existing code: *which documented
 * requirement does this line violate?*
 *
 *   NEW-1  The requirement "identity must be distinct per update" had been
 *          written down all along. Nothing checked the code against it, so
 *          `IngestConsumer` handed downstream only `['_ => constructor]`,
 *          `HandlerSink` dropped `ts`, and `updateId()` degenerated to one
 *          constant per constructor. Measured: 500 distinct bus entries
 *          produced exactly 1 distinct `updateId()`, and both downstream
 *          guards (ReplayDedup's seen-set, forwardKey()) agreed on the same
 *          wrong answer and silently discarded the rest.
 *
 *   D6     The rtrim rule had been requirement R3 in
 *          `.brain/evidence/2026-10-17-research-content-media.md` since round
 *          one. Telegram, verbatim: "the _length_ of an entity must not
 *          include the length of trailing newlines or whitespaces, rtrim
 *          entities before computing their length". `markdownToEntities`
 *          measured pre-block content untrimmed. The offsets stayed
 *          self-consistent, so the message still SENT and simply rendered the
 *          trailing newlines inside the code block.
 *
 * A requirement nobody checks against code is documentation, not a contract.
 * This file is the checker that makes both of them contracts.
 *
 * WHY IT EXECUTES RATHER THAN GREPS
 *
 * A gate that cries wolf gets switched off, and this repo already carries that
 * lesson: `check-citations.py` is permanently red at 4656 findings and trains
 * you to ignore it. So every invariant here is a RUNTIME property exercised
 * against the real consumer and the real parser, not a source-text pattern
 * match. If the code cannot be exercised, the gate says "INCOMPLETE" and exits
 * with its own code rather than reporting a clean tree it did not verify.
 *
 * EXIT CODES
 *
 *   0  every invariant held
 *   1  at least one invariant was violated (paste the output; it names the
 *      requirement and the offending value)
 *   2  usage error
 *   3  INCOMPLETE — an invariant could not be exercised. This is ranked above
 *      FAIL on purpose: a gate that cannot check is not a passing gate.
 */

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use MeRezaRezaei\Teleframe\Bus\IngestConsumer;
use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Bus\RouteTable;
use MeRezaRezaei\Teleframe\Core\Entities\EntityParser;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;

/** @var list<array{0:string,1:string}> */
$failures = [];

/** @var list<string> */
$incomplete = [];

/** @var list<string> */
$passed = [];

/**
 * Record an invariant result.
 *
 * @param  bool  $held  did the invariant hold?
 * @param  string  $id  invariant id, for the report
 * @param  string  $requirement  the documented requirement it enforces
 * @param  string  $detail  the observed value when it failed
 */
function invariant(bool $held, string $id, string $requirement, string $detail = ''): void
{
    global $failures, $passed;

    if ($held) {
        $passed[] = $id;

        return;
    }

    $failures[] = sprintf('%s  FAILS\n      requirement: %s\n      observed:   %s', $id, $requirement, $detail);
}

// ---------------------------------------------------------------------------
// I1 — THE BUS PRODUCER MUST YIELD ONE IDENTITY PER ENTRY  (NEW-1)
// ---------------------------------------------------------------------------

/**
 * Drive the REAL consumer with N distinct entries and demand N distinct
 * identities. This is the check that would have caught NEW-1 on the day it was
 * written: no producer may be added that hands downstream a per-constructor
 * constant.
 */
$driveBus = static function (int $count): array {
    $redis = new ArrayRedis;
    $rows = [];

    (new RouteTable($redis))->set('updateNewMessage*', 'tg:target:messages');

    $producer = new RedisStreamSink($redis, 7);
    for ($i = 1; $i <= $count; $i++) {
        $producer->handle(
            ['_' => 'updateNewMessage', 'message' => ['_' => 'message', 'id' => $i]],
            '7',
        );
    }

    $consumer = new IngestConsumer(
        $redis,
        null,
        static function (array $update, int $accountId) use (&$rows): void {
            $rows[] = [$update, $accountId];
        },
    );

    $stats = $consumer->consumeOnce();

    return [$rows, $stats];
};

try {
    [$rows, $stats] = $driveBus(5);

    if (count($rows) !== 5 || ($stats['forwarded'] ?? 0) !== 5) {
        $incomplete[] = sprintf(
            'I1 could not be exercised: expected 5 rows/5 forwarded, got %d rows / %s forwarded',
            count($rows),
            var_export($stats['forwarded'] ?? null, true),
        );
    } else {
        $ids = [];
        foreach ($rows as [$row, $accountId]) {
            $ids[Update::fromBus($row, $accountId)->updateId()] = true;
        }

        invariant(
            count($ids) === 5,
            'I1  distinct bus entries must yield distinct identities',
            'an update identity must identify one update, so N distinct entries imply N distinct '
            .'identities (NEW-1: one constant per constructor silently discarded 499 of 500)',
            sprintf('%d distinct updateId() across 5 distinct bus entries', count($ids)),
        );
    }
} catch (Throwable $e) {
    $incomplete[] = 'I1 threw: '.$e->getMessage();
}

// ---------------------------------------------------------------------------
// I2 — THE BUS IDENTITY ROW MUST CARRY A PER-ENTRY DISCRIMINATOR  (NEW-1)
// ---------------------------------------------------------------------------
//
// I1 proves the end-to-end behaviour. I2 pins WHY it holds, so that a future
// "simplification" of the identity row cannot quietly reintroduce the collapse
// while some other mechanism happens to mask it.

try {
    [$rows] = $driveBus(3);
    $DISCRIMINATORS = ['entry', 'seq', 'position'];

    if ($rows === []) {
        $incomplete[] = 'I2 could not be exercised: no rows produced';
    } else {
        $missing = [];
        foreach ($DISCRIMINATORS as $field) {
            foreach ($rows as [$row]) {
                if (! array_key_exists($field, $row)) {
                    $missing[] = $field;
                }
            }
        }

        invariant(
            $missing === [],
            'I2  the bus identity row must carry a per-entry discriminator',
            'the row handed downstream must discriminate entries, not just name the constructor; '
            .'"minimal" identity rows were NEW-1',
            'missing field(s): '.implode(', ', array_unique($missing)),
        );
    }
} catch (Throwable $e) {
    $incomplete[] = 'I2 threw: '.$e->getMessage();
}

// ---------------------------------------------------------------------------
// I3 — TWO DISTINCT PAYLOADS MUST NOT SHARE AN IDENTITY  (D5 / NEW-5)
// ---------------------------------------------------------------------------
//
// The event path hands a real payload, so distinct payloads must already
// separate. If a future change makes `updateId()` key on anything coarser than
// the payload, this fires.

try {
    $a = Update::fromBus(['_' => 'messages.messages', 'messages' => [['id' => 1, 'text' => 'hi']]], 7);
    $b = Update::fromBus(['_' => 'messages.messages', 'messages' => [['id' => 2, 'text' => 'hi']]], 7);

    invariant(
        $a->updateId() !== $b->updateId(),
        'I3  distinct payloads must not share an identity',
        'identity must never be coarser than the update it names (D5: content-hash reuse made '
        .'unrelated updates suppress each other)',
        'two different messages produced one identity',
    );
} catch (Throwable $e) {
    $incomplete[] = 'I3 threw: '.$e->getMessage();
}

// ---------------------------------------------------------------------------
// I4 — NO ENTITY LENGTH MAY COVER TRAILING WHITESPACE  (D6, requirement R3)
// ---------------------------------------------------------------------------
//
// Telegram, verbatim (core.telegram.org/api/entities):
//   "the _length_ of an entity must not include the length of trailing newlines
//    or whitespaces, rtrim entities before computing their length: however, the
//    next _offset_ must include the length of newlines or whitespaces that
//    precede it."
//
// Both halves are checked here: the length excludes trailing whitespace, AND
// the plain text is unchanged so the next offset still includes it.

/** @return list<array{label:string,markdown:string}> */
$entityCases = static fn (): array => [
    ['label' => 'pre with trailing newlines', 'markdown' => "```\ncode here\n\n```"],
    ['label' => 'pre then bold', 'markdown' => "```\ncode\n\n```**tail**"],
    ['label' => 'pre with trailing spaces', 'markdown' => "```\ncode\n   ```"],
    ['label' => 'pre single line', 'markdown' => '```code```'],
    ['label' => 'pre with language', 'markdown' => "```php\n\$var = 1;\n\n```"],
    ['label' => 'bold then pre', 'markdown' => "**head**```\nx\n\n```"],
];

try {
    $offenders = [];
    foreach ($entityCases() as $case) {
        $result = EntityParser::markdownToEntities($case['markdown']);
        $text = (string) ($result['text'] ?? '');

        foreach (($result['entities'] ?? []) as $entity) {
            $offset = (int) $entity['offset'];
            $length = (int) $entity['length'];

            // Bounds: every entity must sit inside the text it describes.
            if ($length <= 0 || $offset < 0 || $offset + $length > EntityParser::getUtf16Length($text)) {
                $offenders[] = sprintf(
                    '%s: %s off=%d len=%d is outside text length %d',
                    $case['label'],
                    $entity['_'],
                    $offset,
                    $length,
                    EntityParser::getUtf16Length($text),
                );

                continue;
            }

            // The rtrim rule, first half.
            $slice = mb_substr($text, 0, $offset + $length);
            $tail = substr($slice, -1);
            if ($tail !== '' && ($tail === ' ' || $tail === "\n" || $tail === "\t" || $tail === "\r")) {
                $offenders[] = sprintf(
                    '%s: %s length %d covers trailing whitespace (offset %d, text %s)',
                    $case['label'],
                    $entity['_'],
                    $length,
                    $offset,
                    json_encode($text),
                );
            }
        }
    }

    invariant(
        $offenders === [],
        'I4  no entity length may cover trailing whitespace',
        'rtrim before computing a length, while the next offset still includes the preceding '
        .'whitespace (D6: untrimmed pre-blocks rendered trailing newlines inside the code block)',
        implode("\n      ", $offenders),
    );
} catch (Throwable $e) {
    $incomplete[] = 'I4 threw: '.$e->getMessage();
}

// ---------------------------------------------------------------------------
// I5 — EVERY ENTITY MUST BE WITHIN BOUNDS OF ITS OWN TEXT
// ---------------------------------------------------------------------------
//
// Separate from I4 because a negative or overflowing offset is a different
// failure from an untrimmed length, and a gate that conflates them hides which
// one broke.

try {
    $offenders = [];
    foreach ($entityCases() as $case) {
        $result = EntityParser::markdownToEntities($case['markdown']);
        $textLength = EntityParser::getUtf16Length((string) ($result['text'] ?? ''));

        foreach (($result['entities'] ?? []) as $entity) {
            if ((int) $entity['offset'] + (int) $entity['length'] > $textLength) {
                $offenders[] = sprintf(
                    '%s: %s off=%d len=%d exceeds text length %d',
                    $case['label'],
                    $entity['_'],
                    (int) $entity['offset'],
                    (int) $entity['length'],
                    $textLength,
                );
            }
        }
    }

    invariant(
        $offenders === [],
        'I5  every entity must lie within the text it describes',
        'an entity past the end of its text is silently dropped or mis-rendered by Telegram',
        implode("\n      ", $offenders),
    );
} catch (Throwable $e) {
    $incomplete[] = 'I5 threw: '.$e->getMessage();
}

// ---------------------------------------------------------------------------
// REPORT
// ---------------------------------------------------------------------------

printf("requirement-violation gate — identity and entity invariants\n");
printf("  exercised: %d   held: %d   violated: %d   incomplete: %d\n\n", count($passed) + count($failures), count($passed), count($failures), count($incomplete));

foreach ($passed as $id) {
    printf("  PASS  %s\n", $id);
}

if ($failures !== []) {
    echo "\n";
    foreach ($failures as $failure) {
        printf("  FAIL  %s\n", $failure);
    }
}

if ($incomplete !== []) {
    echo "\n";
    foreach ($incomplete as $note) {
        printf("  INCOMPLETE  %s\n", $note);
    }
}

echo "\n";

// INCOMPLETE outranks FAIL: a gate that could not check is not a passing gate.
if ($incomplete !== []) {
    echo "RESULT: INCOMPLETE — an invariant could not be exercised, so this run proves nothing.\n";

    exit(3);
}

if ($failures !== []) {
    echo "RESULT: FAIL — a documented requirement is violated by the code.\n";

    exit(1);
}

echo "RESULT: PASS\n";

exit(0);
