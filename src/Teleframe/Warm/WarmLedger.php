<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Warm;

use RuntimeException;

/**
 * THE WARM LEDGER — the index of commits already warmed, so none is warmed twice.
 *
 * The owner's rule, and the reason behind it: a warm run touches a REAL Telegram
 * session, so running the same commit twice buys nothing and risks everything.
 * Re-warming a commit re-spends quota against a real account for a result already
 * recorded — and worse, it re-executes whatever that commit added, against the
 * live API, for no new information.
 *
 * So every warmed commit is recorded here by its full SHA, together with the
 * surface it changed and the result. A second run for the same SHA is refused
 * before it reaches the session, not after.
 *
 * WHY THIS IS A PLAIN FILE AND NOT A DATABASE. It has to be readable by a human
 * deciding whether to re-run, it has to survive a wiped runner, and it has to be
 * diffable in a code review — a ledger that decides whether your account is
 * touched again is exactly the file you do not want to be opaque.
 *
 * WHY THE SHA IS THE KEY AND NOT THE BRANCH. A branch moves; a commit does not.
 * Keying on the branch would silently re-warm every commit on a force-push, which
 * is the precise failure this exists to prevent. Keying on the SHA means a
 * rebase produces new SHAs and gets re-warmed, which is correct: a rebased commit
 * is not the artefact that was tested.
 *
 * @see LiveEndpointPolicy for what a warm run is allowed to call at all.
 */
final class WarmLedger
{
    public const VERSION = 1;

    /**
     * $entries is deliberately NOT readonly. `readonly` here was a real bug:
     * record() wrote the file and left this object holding the PRE-write state,
     * so `record($sha); has($sha)` answered "no" and a caller that checked after
     * writing would re-run work it had just recorded. The in-memory state now
     * moves with the file, which is the whole point of an index.
     *
     * @param list<array{sha: string, result: string, at: string, surface: list<string>, note: string|null}> $entries
     */
    public function __construct(
        public array $entries,
        public readonly string $path,
    ) {
    }

    public static function load(string $path): self
    {
        if (! is_file($path)) {
            return new self([], $path);
        }

        $raw = (string) file_get_contents($path);
        if (trim($raw) === '') {
            return new self([], $path);
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            throw new RuntimeException("WarmLedger: {$path} is not valid JSON: " . json_last_error_msg());
        }

        $entries = [];
        foreach ($decoded['warmed'] ?? [] as $entry) {
            if (! is_array($entry) || ! isset($entry['sha'])) {
                throw new RuntimeException("WarmLedger: {$path} holds an entry with no sha.");
            }
            $entries[] = [
                'sha' => (string) $entry['sha'],
                'result' => (string) ($entry['result'] ?? 'unknown'),
                'at' => (string) ($entry['at'] ?? ''),
                'surface' => array_values(array_map('strval', (array) ($entry['surface'] ?? []))),
                'note' => isset($entry['note']) ? (string) $entry['note'] : null,
            ];
        }

        return new self($entries, $path);
    }

    public function has(string $sha): bool
    {
        return $this->entryFor($sha) !== null;
    }

    /** @return array{sha: string, result: string, at: string, surface: list<string>, note: string|null}|null */
    public function entryFor(string $sha): ?array
    {
        foreach ($this->entries as $entry) {
            if (hash_equals($entry['sha'], $sha)) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Record a warmed commit. Re-recording the SAME (sha, result) pair is a
     * no-op, which is what makes this safe to call from a retrying runner: the
     * operation is idempotent rather than append-forever.
     *
     * @param list<string> $surface
     */
    public function record(string $sha, string $result, array $surface, ?string $note = null, ?string $at = null): void
    {
        $sha = self::normalise($sha);
        $existing = $this->entryFor($sha);
        if ($existing !== null && $existing['result'] === $result) {
            return;
        }

        $entries = $this->entries;
        foreach ($entries as $i => $entry) {
            if (hash_equals($entry['sha'], $sha)) {
                unset($entries[$i]);
            }
        }
        $entries[] = [
            'sha' => $sha,
            'result' => $result,
            'at' => $at ?? gmdate('c'),
            'surface' => array_values(array_unique(array_map('strval', $surface))),
            'note' => $note,
        ];

        usort($entries, static fn (array $a, array $b): int => strcmp($b['at'], $a['at']));

        $this->write($entries);
        $this->entries = $entries;
    }

    /** @param list<array{sha: string, result: string, at: string, surface: list<string>, note: string|null}> $entries */
    private function write(array $entries): void
    {
        $dir = dirname($this->path);
        if (! is_dir($dir) && ! mkdir($dir, 0o775, true) && ! is_dir($dir)) {
            throw new RuntimeException("WarmLedger: cannot create {$dir}");
        }

        $payload = json_encode(
            [
                '_comment' => 'Index of commits already warmed against a REAL Telegram session. '
                    . 'A SHA listed here is never warmed again. Managed by WarmLedger; '
                    . 'see config/live-endpoint-policy.php for what a warm run may call.',
                'version' => self::VERSION,
                'warmed' => $entries,
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        );

        if ($payload === false) {
            throw new RuntimeException('WarmLedger: json_encode failed: ' . json_last_error_msg());
        }

        if (file_put_contents($this->path, $payload . "\n", LOCK_EX) === false) {
            throw new RuntimeException("WarmLedger: cannot write {$this->path}");
        }
    }

    /** A SHA is 40 hex chars; anything else is a caller bug, caught here not in the wire. */
    public static function normalise(string $sha): string
    {
        $sha = strtolower(trim($sha));
        // Strictly zero-regex: phpstan bans preg_* outside src/Laravel. A git
        // SHA is exactly 40 hex chars, so this is both the rule and the proof.
        $isHex = $sha !== '' && strlen($sha) === 40 && strspn($sha, '0123456789abcdef') === 40;
        if (! $isHex) {
            throw new RuntimeException("WarmLedger: '{$sha}' is not a full 40-character commit SHA. "
                . 'A short SHA is refused on purpose: it can collide, and a collision means '
                . 'skipping a real warm run.');
        }

        return $sha;
    }

    /** @return list<string> */
    public function warmedShas(): array
    {
        return array_map(static fn (array $e): string => $e['sha'], $this->entries);
    }
}
