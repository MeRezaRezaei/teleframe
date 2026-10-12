<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Laravel\SchemaArtifacts;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;

/**
 * Backfill and the public ingest API write the SAME child tables the mirror
 * decomposer writes — and this is the gate that keeps it that way.
 *
 * ## The defect
 *
 * `UpdateIngestor::writeMessage()` used to close its child write with
 * `foreach (['from_id', 'saved_peer_id'] as $field)`. Three shipped entry
 * points reach that writer and none of them is the bus:
 *
 *  - `TeleframeServiceProvider.php` binds `BackfillCommand::INGESTER_KEY` to a
 *    DIRECT `$ingestor->ingest(['_' => 'messages.messages', 'messages' => …])`,
 *    consumed by `BackfillCommand` — no mirror ingester involved;
 *  - `src/Teleframe/Teleclient.php` and `src/Teleframe/Teleframe.php` call
 *    `UpdateIngestor::ingest` directly, and `docs/ingest.md:26` documents that
 *    as the primary API;
 *  - only `Bus\IngestConsumer` routes to the mirror ingester, and Cycle 37
 *    already proved THAT path does not reach `writeMessage` at all.
 *
 * `mirror-catalog.json` declares 29 direct children for `tf_messages`; the
 * two-field loop wrote 2 of them. So `teleframe:backfill` stored the message
 * body and nothing else that is a message — no reply graph, no forward header,
 * no view/forward counters, no entities — while landing in the SAME tables as
 * the live bus with DIFFERENT contents for the same message. Identical
 * schemas, so no host could tell a backfilled row from a live one. Cycle 36
 * fixed one such field on the mirror side alone (commit 3a1f4e50), which is
 * how this became a shipped wrong fact rather than a known gap.
 *
 * ## The rejected alternative, and why this test is shaped around B
 *
 * Routing these callers through `MirrorIngesterFactory::build()` was measured
 * and does not work: `MirrorCatalog::tableForCtor('messages.messages')` is
 * NULL (so the factory closure early-returns and stores ZERO tables — a
 * 3-table write becomes a 0-table one), its callable returns `void` where
 * `BackfillCommand` needs `array{stored, root}` and calls `$root->getKey()`,
 * and `SelfOriginatedClassifier` suppresses `UpdateStored` for
 * self-originated messages — i.e. for exactly the history backfill fetches.
 * So the duplication was removed on the ingestor side instead, and the child
 * set is now derived from the catalog in one place.
 *
 * ## Why the expected set is DERIVED, never listed
 *
 * The assertion set is computed from `MirrorTableResolver` + the payload: a
 * declared child is expected exactly when the payload carries its field. A
 * hardcoded list would be a second copy that drifts — the disease this test
 * exists to kill. So adding a child to the catalog moves BOTH the expected
 * set and the live expectation at once, and the only way this test can go red
 * is if one writer stops producing a child the other produces.
 *
 * Provenance: src/Teleframe/Ingest/UpdateIngestor.php::writeChildrenFromCatalog
 * (the catalog-driven walk that replaced the two-field loop);
 * src/Schema/Mirror/MirrorFactDecomposer.php:163 (decomposeChildren);
 * src/Schema/Mirror/MirrorTableResolver.php (the `{parent}_{field}` naming
 * this test inverts to get each child's payload field);
 * src/Laravel/Providers/TeleframeServiceProvider.php:324-342 (the backfill
 * binding) and :121/:159 (the Teleclient / Teleframe bindings).
 */
final class MessageChildSetParityTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    private const MESSAGE_ID = 4242;

    private const PARENT = 'tf_messages';

    /**
     * Tenant 7 (`ACCOUNT`) — the only one.
     *
     * This file drives the decomposition two ways that share one tenant: the
     * public `UpdateIngestor::ingest(…, self::ACCOUNT)` arm and the
     * `MirrorDecomposer::decompose(self::PARENT, self::ACCOUNT, …)` arm whose
     * writes it compares against. Both land in `tf_messages` and the catalog's
     * child tables, all of which carry `account_id` = the Telegram user id —
     * `MirrorIngesterFactory.php:160` writes the caller's Telegram id straight
     * in, and the FK targets `telegram_accounts.user_id`, not the vault row's
     * own `id`. The parity claim is about the CHILD SET, not about tenancy, so
     * one tenant is the whole story here.
     *
     * Production always plants the parent first:
     * `VaultAddAccountCommand.php:80` writes `user_id` at account-add time.
     *
     * @return list<int>
     */
    protected function ingestTenantIds(): array
    {
        return [7];
    }

    /**
     * A realistic rich `message` — the shape backfill actually receives.
     *
     * It carries one field per child kind that matters here: two peer children
     * (from_id, saved_peer_id), two nested-object children (fwd_from, reply_to),
     * one vector child (entities) and two scalar children (views, forwards).
     * Before the fix only from_id and saved_peer_id were written; the other
     * four rows simply did not exist for a backfilled message.
     *
     * `media` is deliberately present and deliberately produces NO expected
     * child: `tf_message_medias` is a SHARED catalog node (its `parentTf` is
     * not tf_messages), so the children walk skips it by design and it belongs
     * to its own ingest context. See the limit noted in the report.
     *
     * @return array<string, mixed>
     */
    private static function messagePayload(): array
    {
        return [
            '_' => 'message',
            'id' => self::MESSAGE_ID,
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => 1737473577],
            'date' => 1735689600,
            'message' => 'the backfilled body',
            'out' => false,
            'from_id' => ['_' => 'peerUser', 'user_id' => 501558149],
            'saved_peer_id' => ['_' => 'peerChat', 'chat_id' => 555],
            'fwd_from' => ['_' => 'messageFwdHeader', 'date' => 1735689000, 'from_id' => ['_' => 'peerUser', 'user_id' => 3]],
            'reply_to' => ['_' => 'messageReplyHeader', 'reply_to_msg_id' => 7, 'reply_to_peer_id' => ['_' => 'peerUser', 'user_id' => 9]],
            'entities' => [
                ['_' => 'messageEntityBold', 'offset' => 0, 'length' => 3],
                ['_' => 'messageEntityItalic', 'offset' => 4, 'length' => 7],
            ],
            'views' => 12,
            'forwards' => 2,
            'media' => [
                ['_' => 'messageMediaPhoto', 'photo' => ['_' => 'photoEmpty', 'id' => 0]],
            ],
        ];
    }

    /**
     * The whole point: the SAME payload, through the BACKFILL ENVELOPE the
     * provider binds, and through the REAL decomposer, write the same set of
     * child tables.
     *
     * Both arms are measured the same way — "which catalog-declared child
     * tables hold a row for this message" — against a schema produced by
     * EXECUTING the shipped migrations, so neither arm is transcribed and a
     * migration that moves moves the measurement.
     */
    public function test_backfill_envelope_and_decomposer_write_the_same_child_tables(): void
    {
        $payload = self::messagePayload();
        $expected = $this->expectedChildTables($payload);

        self::assertNotSame([], $expected, 'the catalog declares no child for this payload — the derivation is stale, and the assertion below would pass vacuously');

        // ORDER IS LOAD-BEARING. `ShippedStoreSchema::capsuleFor()` installs its
        // own container as the global one (that is how the shipped migrations
        // resolve `Schema::`), so the reference arm runs FIRST and hands the
        // global container back before the testbench app is needed again.
        // Running it second would leave the app's facade root pointing at a
        // throwaway sqlite capsule.
        $viaDecomposer = $this->childTablesWrittenByDecomposer($payload);
        $viaBackfill = $this->childTablesWrittenByBackfillEnvelope($payload);

        self::assertSame(
            $expected,
            $viaDecomposer,
            'the decomposer no longer writes every catalog-declared child the payload carries — the expected set is derived from the catalog, so this is a real regression, not a stale fixture',
        );

        self::assertSame(
            $viaDecomposer,
            $viaBackfill,
            "backfill and the mirror decomposer disagree about a message's children.\n"
            .'only backfill: '.json_encode(array_values(array_diff($viaBackfill, $viaDecomposer)))."\n"
            .'only decomposer: '.json_encode(array_values(array_diff($viaDecomposer, $viaBackfill))),
        );
    }

    /**
     * The proof this gate is FALSIFIABLE, not a tautology: the pre-fix
     * two-field loop, executed here in six lines, does not satisfy it.
     *
     * If this assertion ever stops holding, the gate has gone blind — most
     * likely because someone re-introduced an enumeration and the test started
     * agreeing with it. It is the RED side of the gate, stated as a fact
     * about the defect rather than left as something a future reader has to
     * take on trust.
     */
    public function test_the_old_two_field_loop_would_not_satisfy_the_gate(): void
    {
        $payload = self::messagePayload();
        $expected = $this->expectedChildTables($payload);

        // Exactly what writeMessage() used to do, as a table set.
        $oldLoop = [];
        foreach (['from_id', 'saved_peer_id'] as $field) {
            if (! is_array($payload[$field] ?? null)) {
                continue;
            }

            $oldLoop[] = self::PARENT.'_'.$field;
        }
        sort($oldLoop);

        self::assertNotSame(
            $expected,
            $oldLoop,
            'the pre-fix two-field loop now satisfies the gate — either the defect was never what this test claims, or the child set is being derived from something that is not the catalog',
        );

        // And the two the old loop did write are genuinely still written, so
        // this is a "wrote too little" story and not a "renamed everything".
        self::assertSame(
            [self::PARENT.'_from_id', self::PARENT.'_saved_peer_id'],
            $oldLoop,
            'the control no longer models the pre-fix defect — the two peer children it wrote are gone or renamed',
        );
    }

    /**
     * Arm 1 — the BACKFILL ENVELOPE, through the real `UpdateIngestor`.
     *
     * The envelope shape is the one `TeleframeServiceProvider` binds for
     * `BackfillCommand::INGESTER_KEY` and the one `BackfillCommand` hands it
     * per fetched page. Nothing is asserted about the RETURN here on purpose:
     * `ingest()` returns the first stored root, and this test is about the
     * rows on disk, which is where the divergence was invisible.
     *
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function childTablesWrittenByBackfillEnvelope(array $payload): array
    {
        (new UpdateIngestor)->ingest([
            '_' => 'messages.messages',
            'messages' => [$payload],
            'chats' => [],
            'users' => [],
        ], self::ACCOUNT);

        return $this->childTablesHoldingARow();
    }

    /**
     * Arm 2 — the REAL decomposer, against the SAME shipped schema.
     *
     * Uses `ShippedStoreSchema::capsuleFor()` over the whole shipped migration
     * set, so the rows are placed into the DDL the package actually ships.
     *
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function childTablesWrittenByDecomposer(array $payload): array
    {
        $container = Container::getInstance();
        $capsule = ShippedStoreSchema::capsuleFor(...$this->shippedMigrationPaths());

        try {
            $db = $capsule->getConnection();

            $scheme = TlParser::parseFile(SchemaArtifacts::tl());
            $catalog = MirrorCatalog::load(SchemaArtifacts::catalog(), $scheme);
            $decomposer = new MirrorFactDecomposer(new MirrorTableResolver($catalog, $scheme), $catalog);

            $result = $decomposer->decompose(self::PARENT, self::ACCOUNT, $payload);
            $written = (new MirrorFactWriter)->write($db, $result['rows']);

            self::assertSame([], $written['fkClues'], 'the reference arm raised an FK clue — the parity comparison would be meaningless');
            self::assertSame([], $written['unmappedClues'], 'the reference arm named a column the shipped DDL lacks — the parity comparison would be meaningless');

            return $this->childTablesHoldingARow($db);
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($container);
        }
    }

    /**
     * Which catalog-declared children of tf_messages actually hold a row for
     * this message — MEASURED, by asking the database.
     *
     * The candidate list comes from the resolver, not from a literal, so a
     * child added to the catalog is measured here without touching this file.
     *
     * @return list<string>
     */
    private function childTablesHoldingARow(?Connection $db = null): array
    {
        $db ??= DB::connection();

        $found = [];
        foreach ($this->declaredChildren() as $table) {
            if (! $db->getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            $exists = $db->table($table)
                ->where('account_id', self::ACCOUNT)
                ->where('id', self::MESSAGE_ID)
                ->exists();
            if ($exists) {
                $found[] = $table;
            }
        }

        sort($found);

        return $found;
    }

    /**
     * The EXPECTED set, derived from the catalog + the payload: a declared
     * child is expected exactly when the payload carries the field it is named
     * after. The naming is inverted from `MirrorTableResolver`'s
     * `{$parentTf}_{$fieldName}`, so the two cannot disagree about it.
     *
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function expectedChildTables(array $payload): array
    {
        $expected = [];
        foreach ($this->declaredChildren() as $table) {
            $field = substr($table, strlen(self::PARENT) + 1);
            $value = $payload[$field] ?? null;
            if ($value === null || $value === []) {
                continue; // row existence = fact existence: an absent fact is no row
            }

            $expected[] = $table;
        }

        sort($expected);

        return $expected;
    }

    /**
     * Every DIRECT child the catalog declares for tf_messages.
     *
     * `parentTf` is checked because `MirrorFactDecomposer::decomposeChildren`
     * skips shared catalog nodes (tf_message_medias, tf_chats, …) for the same
     * reason: they have their own key and their own ingest context. Including
     * them here would make this test demand rows the real decomposer
     * deliberately never emits.
     *
     * @return list<string>
     */
    private function declaredChildren(): array
    {
        $scheme = TlParser::parseFile(SchemaArtifacts::tl());
        $catalog = MirrorCatalog::load(SchemaArtifacts::catalog(), $scheme);
        $table = (new MirrorTableResolver($catalog, $scheme))->resolveAll([self::PARENT])[0];

        $children = [];
        foreach ($table->children as $child) {
            if ($child->parentTf !== self::PARENT) {
                continue; // shared node — not a child of this parent
            }

            $children[] = $child->tfName;
        }

        sort($children);

        return $children;
    }

    /**
     * @return list<string>
     */
    private function shippedMigrationPaths(): array
    {
        $paths = glob(self::PACKAGE_ROOT.'/src/Laravel/Migrations/*.php') ?: [];
        sort($paths);

        self::assertNotSame([], $paths, 'no shipped migrations found — the reference arm would measure an empty schema');

        return array_values($paths);
    }

    private const PACKAGE_ROOT = __DIR__.'/../..';
}
