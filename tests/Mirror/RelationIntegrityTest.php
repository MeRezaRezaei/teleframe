<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Mirror;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Mirror\Models\TfChannel;
use MeRezaRezaei\Teleframe\Mirror\Models\TfChannelParticipant;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessageEntity;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessageMedia;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessageReplyMarkupRows;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;

/**
 * Cross-domain FK relation-completeness proof (plan Task 8).
 *
 * The last migration in the curated dial (2026_09_14_299999) is the single
 * place where FKs that cross curated-domain boundaries live. Every relation
 * the curated models document across tables must be backed by a real FK in
 * that migration — and every FK it declares must be accounted for here.
 *
 * Verified against the ACTUAL curated migrations (not the plan's working
 * column names — several differ):
 *  - tf_messages_media / tf_messages_entities carry the parent message key as
 *    the WHOLE peer-scoped key with NO separate message_id column:
 *    (account_id, peer_type, peer_id, id), plus (message_peer_type,
 *    message_peer_id, id) on the media child — that table already owns
 *    `peer_type`/`peer_id` for a peer inside its own payload, so the enclosing
 *    message's scope is prefixed. A FK names REFERENCED columns, so the
 *    prefixed local legs still reference the parent's unprefixed ones;
 *  - the polymorphic peer pair (peer_type/peer_id) on tf_messages / tf_dialogs
 *    and the media→documents/photos refs from the plan are NOT physically
 *    constraining (one FK cannot branch on peer_type; the curated media table
 *    has no document/photo FK columns) — enforced in app code instead, see the
 *    299999 migration docblock.
 *  - tf_messages_reply_markup_rows_buttons → tf_messages_reply_markup_rows is
 *    declared on FIVE legs (account_id, peer_type, peer_id, id,
 *    parent_position), not one: parent_position is not unique within a message,
 *    so the shared message key is pinned alongside it — and since 2026-10-03
 *    that shared key is peer-scoped, because Telegram numbers messages PER
 *    CHAT and `id` alone names no message. These are exactly the legs
 *    TfMessageReplyMarkupRows::buttons() adds to its own where() clause, and
 *    what the composite buttons primary key (widened in 200010) forces.
 *
 * Runs the full UpdateIngestor::migrationPaths() dial on sqlite :memory: with
 * PRAGMA foreign_keys = ON, so the FK proof is behavioral, not source-parsed.
 */
final class RelationIntegrityTest extends IngestTestCase
{
    /**
     * The tenants this fixture writes under.
     *
     * MEASURED, not assumed: extracted from the account ids this file
     * actually inserts. It was undeclared, and with foreign-key enforcement
     * on (19 constraints) every row was refused at the DATABASE rather than
     * at the code under test — the test failed on its own fixture.
     *
     * Declared HERE rather than by relaxing seedIngestTenant(): that helper
     * contracts that each test declares the tenants it means to use, so a
     * test writing under an unplanted id fails LOUDLY. These were failing
     * loudly, which is the mechanism working. A blanket auto-seed would also
     * make the orphan-refusal tests unable to plant an orphan.
     *
     * ## `self::ACCOUNT` ONLY — NOT `self::ACCOUNT_TENANT`, AND NOT BOTH
     *
     * MEASURED, and the reason is mechanical. `seedIngestTenant()` plants
     * `telegram_accounts.user_id = <the id given>`, and `user_id` carries the
     * `telegram_accounts_user_id_unique` index (2026_10_04_000100). Declaring
     * `self::ACCOUNT_TENANT` here therefore DOUBLE-PLANTS it — `insertAccount()`
     * below then inserts a second row with the same `user_id`, and every test
     * that calls it died on
     * `UNIQUE constraint failed: telegram_accounts.user_id`. Measured before
     * this correction, at `ingestTenantIds(): return [41, self::ACCOUNT_TENANT]`.
     *
     * Nothing is lost by leaving `ACCOUNT_TENANT` out: the two tests that write
     * under it (`test_participant_orphan_without_channel_is_rejected` and
     * `test_channel_delete_is_restricted_while_participants_exist`) both call
     * `insertAccount()`, which plants it deliberately — with `id` and `user_id`
     * deliberately DIFFERENT, which is what lets this file discriminate the
     * retargeted tenant constraint from the one it replaced.
     */
    protected function ingestTenantIds(): array
    {
        return [self::ACCOUNT];
    }

    private const ACCOUNT = 41;

    /**
     * The TENANT key a `tf_*.account_id` carries — `telegram_accounts.user_id`,
     * deliberately a DIFFERENT value from `self::ACCOUNT`.
     *
     * Since 983e473b the six tenant constraints aim at `telegram_accounts.user_id`,
     * not at the vault row's `id`: the engine writes the TELEGRAM USER id into
     * `account_id`, so `id` is a surrogate and `user_id` is the tenant. A fixture
     * that gave the account row the SAME number for both would satisfy either
     * target and therefore pin nothing — it could not tell the retargeted
     * constraint from the constraint it replaced. The two keys are kept distinct
     * here precisely so the fixture discriminates: a child row naming only
     * `self::ACCOUNT` MUST be refused (see the probe in
     * test_channel_delete_is_restricted_while_participants_exist).
     */
    private const ACCOUNT_TENANT = 1724372757;

    private const MIGRATION_FILE = '2026_09_14_299999_create_tf_foreign_keys.php';

    /**
     * The promised cross-domain FKs: child table → FK columns → parent table
     * → on-delete action. `model` / `relation` name the Eloquent relation that
     * must exist on the model side; null means the FK is a canonical peer ref
     * with no modeled belongsTo (the participant keeps its composite-key child
     * accessors instead — see TfChannelParticipant).
     *
     * @var list<array{child:string, fk:list<string>, parent:string, action:string, model?:class-string, relation?:string}>
     */
    private const CROSS_DOMAIN_FKS = [
        // message 1:1 media child → content message (child→parent cascade).
        ['child' => 'tf_messages_media', 'fk' => ['account_id', 'message_peer_type', 'message_peer_id', 'id'], 'parent' => 'tf_messages', 'action' => 'CASCADE', 'model' => TfMessage::class, 'relation' => 'media'],
        // message 1:N entities vector child → content message (child→parent cascade).
        ['child' => 'tf_messages_entities', 'fk' => ['account_id', 'peer_type', 'peer_id', 'id'], 'parent' => 'tf_messages', 'action' => 'CASCADE', 'model' => TfMessage::class, 'relation' => 'entities'],
        // keyboard button → its positioned markup row (child→parent cascade). The
        // FK carries FIVE legs, not one: parent_position alone is not unique
        // within a message, so the shared message key — which is itself
        // peer-scoped, since Telegram numbers messages per chat — is pinned
        // alongside it. Same legs TfMessageReplyMarkupRows::buttons() adds.
        ['child' => 'tf_messages_reply_markup_rows_buttons', 'fk' => ['account_id', 'peer_type', 'peer_id', 'id', 'parent_position'], 'parent' => 'tf_messages_reply_markup_rows', 'action' => 'CASCADE', 'model' => TfMessageReplyMarkupRows::class, 'relation' => 'buttons'],
        // channel participant → channel (canonical peer ref, RESTRICT).
        ['child' => 'tf_channel_participants', 'fk' => ['account_id', 'channel_id'], 'parent' => 'tf_channels', 'action' => 'RESTRICT'],
    ];

    /**
     * The CHILD ATTRIBUTE each belongsTo must read the enclosing message's peer
     * scope FROM — per child, never one shared value.
     *
     * PER CHILD, and that is the whole point. These two tables spell the
     * enclosing message's scope DIFFERENTLY: `tf_messages_media` already owns
     * `peer_type`/`peer_id` for a peer inside the `MessageMedia` union payload,
     * so its message scope lives in the PREFIXED `message_peer_type`/
     * `message_peer_id`; `tf_messages_entities` uses the short names.
     *
     * The relation's LEG COLUMNS are the parent's `peer_type`/`peer_id` either
     * way — a `belongsTo` filters `tf_messages` — so the leg NAMES are identical
     * and a shared name-constant proves nothing. What differs, and what was
     * wrong, is the ATTRIBUTE THE VALUE IS READ FROM. A single shared
     * `['peer_type','peer_id']` leg expectation made
     * `TfMessageMedia::message()`'s wrong reading look right: it pinned the
     * media PAYLOAD peer (0 on every ordinary media row), so the relation
     * resolved to NOTHING for every real row in the table — and the assertion
     * meant to prevent exactly that matched anyway.
     *
     * ## BOTH LEGS ARE CARRIED EXPLICITLY, AND NEVER DERIVED BY CONCATENATION
     *
     * An earlier revision stored ONE prefix here and built the id half as
     * `$source.'_id'`. That is the exact mechanical assumption this task
     * exists to kill: the id leg is NOT spelled `message_peer_type_id`, and on
     * `tf_messages_entities` the short prefix gives `peer_type_id`, which is
     * also not a column. Both are nonexistent attributes, and Eloquent answers
     * a nonexistent attribute with `null` rather than raising — so the
     * expectation silently became `['peer_type' => 3, 'peer_id' => null]`, the
     * test failed for a reason that had nothing to do with the code, and the
     * entity half never ran at all because the media half threw first.
     *
     * Two explicit column names per child cannot be produced by a concat, and
     * `test_model_relations_resolve_to_the_fk_pairs` additionally asserts BOTH
     * are real attributes of the child before they are read — at the guard
     * immediately after the child is constructed — so a misspelling fails
     * loudly with "this attribute does not exist on this model" instead of
     * quietly becoming `null` and turning the expectation below into a
     * null-valued one.
     *
     * THE CORRECTION (2026-10-03, Task 2.2 fix round 2): that claim was FALSE
     * until round 2. The paragraph above describes exactly the quiet-`null`
     * failure this constant's shape was invented to prevent, and the guard that
     * paragraph promised was never written — so the file asserted the absence
     * of a guard it documented in detail, and the dead `[$typeAttr, $idAttr]`
     * destructure at the old line 253 was its fossil: two variables assigned and
     * never read, which is the shape a removed guard leaves behind. The hole is
     * now closed for real.
     *
     * @var array<class-string, array{0: string, 1: string}> [type attribute, id attribute]
     */
    private const BELONGS_TO_PEER_SOURCE = [
        TfMessageMedia::class => ['message_peer_type', 'message_peer_id'],
        TfMessageEntity::class => ['peer_type', 'peer_id'],
    ];

    private const BELONGS_TO_SIDE = [
        TfMessageMedia::class => [TfMessage::class, 'message'],
        TfMessageEntity::class => [TfMessage::class, 'message'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // FK enforcement on for the whole class: every assertion in here is a
        // behavioral FK proof, not a source grep.
        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function test_migration_is_in_the_curated_ship_dial(): void
    {
        self::assertFileExists(
            dirname(__DIR__, 2).'/src/Laravel/Migrations/'.self::MIGRATION_FILE,
            self::MIGRATION_FILE.' must be shipped in src/Laravel/Migrations',
        );

        $applied = DB::table('migrations')->pluck('migration')->map(
            static fn (string $row): bool => str_contains($row, 'create_tf_foreign_keys'),
        );
        self::assertContains(true, $applied, 'the FK migration must be applied by the full dial');
    }

    public function test_every_promised_cross_domain_fk_exists_in_the_migrated_schema(): void
    {
        foreach (self::CROSS_DOMAIN_FKS as $expected) {
            $actual = $this->foreignKeys($expected['child']);
            $match = array_values(array_filter($actual, static function (array $fk) use ($expected): bool {
                return $fk['columns'] === $expected['fk']
                    && $fk['foreign_table'] === $expected['parent']
                    && strtoupper((string) $fk['on_delete']) === $expected['action'];
            }));

            self::assertCount(1, $match, sprintf(
                'expected %s (%s) → %s %s; found %s',
                $expected['child'],
                json_encode($expected['fk']),
                $expected['parent'],
                $expected['action'],
                json_encode(array_map(static fn (array $f): string => $f['foreign_table'].':'.$f['on_delete'], $actual)),
            ));
        }

        // RAISED (2026-10-06): every one of these children also carries the
        // TENANT key `2026_10_04_000700` added — `(account_id) ->
        // telegram_accounts(user_id)`, CASCADE. Partitioning that key off the
        // cross-domain count above would be an unearned exemption if the tenant
        // key itself were never asserted anywhere, so it is asserted here: one
        // single-column key to `telegram_accounts(user_id)`, and nothing else
        // pointing at the vault.
        foreach (self::CROSS_DOMAIN_FKS as $expected) {
            $tenantKeys = array_values(array_filter(
                $this->foreignKeys($expected['child']),
                static fn (array $fk): bool => $fk['foreign_table'] === 'telegram_accounts',
            ));

            self::assertCount(1, $tenantKeys, sprintf(
                '%s must carry exactly one tenant foreign key; found %s',
                $expected['child'],
                json_encode(array_map(static fn (array $f): string => implode(',', $f['columns']).' -> '.$f['foreign_table'].'('.$f['on_delete'].')', $tenantKeys)),
            ));
            self::assertSame(['account_id'], $tenantKeys[0]['columns']);
            self::assertSame(['user_id'], $tenantKeys[0]['foreign_columns'], sprintf(
                '%s\'s tenant key must name telegram_accounts.USER_ID — the retarget D-005 settled — not .id',
                $expected['child'],
            ));
            self::assertSame('CASCADE', strtoupper((string) $tenantKeys[0]['on_delete']), sprintf(
                '%s must cascade away with its tenant',
                $expected['child'],
            ));
        }
    }

    public function test_no_promised_fk_columns_are_nullable(): void
    {
        foreach (self::CROSS_DOMAIN_FKS as $fk) {
            $nullable = Schema::getColumns($fk['child']);
            foreach ($fk['fk'] as $column) {
                $col = array_values(array_filter($nullable, static fn (array $c): bool => $c['name'] === $column))[0] ?? null;
                self::assertNotNull($col, "{$fk['child']}.{$column} must exist");
                self::assertFalse((bool) $col['nullable'], "{$fk['child']}.{$column} must be NOT NULL");
            }
        }
    }

    public function test_model_relations_resolve_to_the_fk_pairs(): void
    {
        $message = new TfMessage(['account_id' => self::ACCOUNT, 'id' => 5, 'constructor' => 'message']);

        // RAISED (2026-10-03, Task 2.2): these relations used to be asserted on
        // a SINGLE `id` leg. They are now peer-scoped, because Telegram numbers
        // messages PER CHAT — `id=5` exists in essentially every dialog of a
        // real account — so an `id`-only leg resolves to whichever dialog wrote
        // last. The media child is the one with PREFIXED local legs, because it
        // already owns peer_type/peer_id for a peer inside its own payload.
        $media = $message->media();
        self::assertInstanceOf(HasOne::class, $media, 'TfMessage::media() must be a hasOne — one media row per message');
        self::assertSame('id', $media->getForeignKeyName(), 'the id leg stays a single column — Eloquent takes relation keys as STRINGS, so the peer legs are pinned as where-clauses (see ResolvesMessagePeerScope)');
        self::assertSame('id', $media->getLocalKeyName(), 'the media relation must join on the parent id column, never on a media table column');
        self::assertSame('tf_messages_media', $media->getRelated()->getTable(), 'the media relation must target tf_messages_media, the prefixed-legs table');
        self::assertSame(
            ['account_id', 'message_peer_type', 'message_peer_id'],
            $this->shortLegs($this->pinnedLegs($media)),
            'TfMessage::media() must pin the tenant leg AND both peer legs, on the PREFIXED columns — this table '
            .'already owns peer_type/peer_id for a peer inside its own payload, so the enclosing message\'s scope '
            .'cannot reuse the short names.',
        );

        $entities = $message->entities();
        self::assertInstanceOf(HasMany::class, $entities, 'TfMessage::entities() must be a hasMany — the entities vector is a list');
        self::assertSame('id', $entities->getForeignKeyName(), 'the entities relation key stays the single column id; the peer legs are pinned as where-clauses');
        self::assertSame('id', $entities->getLocalKeyName(), 'the entities relation must join on the parent id column, never on an entities table column');
        self::assertSame('tf_messages_entities', $entities->getRelated()->getTable(), 'the entities relation must target tf_messages_entities, the short-legs table');
        self::assertSame(
            ['account_id', 'peer_type', 'peer_id'],
            $this->shortLegs($this->pinnedLegs($entities)),
            'TfMessage::entities() must pin the WHOLE declared key — the tenant leg AND both peer legs. '
            .'The foreign key is (account_id, peer_type, peer_id, id): an id-only leg returns the same fact out '
            .'of a DIFFERENT dialog, and a peer-only-but-tenantless leg returns ANOTHER TENANT\'s fact, which is '
            .'a cross-tenant leak rather than a wrong-dialog read.',
        );

        // The button relation is the one whose key is NOT a single column: it
        // joins the row's `position` to the button's `parent_position` and then
        // pins the shared message id leg. Both halves are asserted against the
        // FK below, so the relation and the declared constraint cannot drift
        // apart — the buttons table's own `position` must never appear as a
        // parent-side leg.
        $row = new TfMessageReplyMarkupRows(['account_id' => self::ACCOUNT, 'id' => 5, 'position' => 2]);
        $buttons = $row->buttons();
        self::assertInstanceOf(HasMany::class, $buttons, 'TfMessageReplyMarkupRows::buttons() must be a hasMany — a row carries several buttons');
        self::assertSame('parent_position', $buttons->getForeignKeyName(), 'the button side of the relation must be parent_position, never the button\'s own position');
        self::assertSame('position', $buttons->getLocalKeyName(), 'the row side of the relation must be the row\'s position');
        self::assertSame('tf_messages_reply_markup_rows_buttons', $buttons->getRelated()->getTable(), 'the buttons relation must target the buttons child table, never the row table itself');
        self::assertSame(
            ['account_id', 'peer_id', 'peer_type'],
            $this->onlyPeerAndTenantLegs($this->shortLegs($this->pinnedLegs($buttons))),
            'the buttons relation must pin the tenant leg and both peer legs as well as the id leg. The declared '
            .'key is (account_id, peer_type, peer_id, id, parent_position): a row in one dialog otherwise returns the '
            .'buttons of the same-position row in another, and without the tenant leg it returns another tenant\'s.',
        );

        $buttonFk = $this->crossDomainKeys('tf_messages_reply_markup_rows_buttons');
        self::assertCount(1, $buttonFk, 'tf_messages_reply_markup_rows_buttons must carry exactly one cross-domain FK');
        self::assertSame(['account_id', 'peer_type', 'peer_id', 'id', 'parent_position'], $buttonFk[0]['columns'], 'the FK must carry the whole peer-scoped message key as well as parent_position, or it is not unique within a message');
        self::assertSame('tf_messages_reply_markup_rows', $buttonFk[0]['foreign_table'], 'the buttons cross-domain FK must name tf_messages_reply_markup_rows as its parent');
        self::assertSame('CASCADE', strtoupper((string) $buttonFk[0]['on_delete']), 'a positioned vector child cascades with its parent row, exactly as entities cascade with their message');

        foreach (self::BELONGS_TO_SIDE as $childClass => [$parentClass, $method]) {
            // The child carries BOTH peer pairs, deliberately set to DIFFERENT
            // values: the payload peer it owns itself, and the enclosing
            // message's scope under whichever prefix that table uses for it.
            // Reading the wrong one is therefore a wrong VALUE, not a null —
            // which is what makes the assertion below able to see it at all.
            //
            // THE GUARD THE CONSTANT'S DOCBLOCK PROMISED (Task 2.2 round 2):
            // Eloquent answers a nonexistent attribute with `null` instead of
            // raising, so a misspelling in BELONGS_TO_PEER_SOURCE would
            // otherwise make `$child->{$idAttr}` null, the expectation below
            // would compare `null` against the leg value, and the test would
            // fail on a TYPO IN ITS OWN CONSTANT while saying nothing about the
            // relation. That is precisely the defect this constant's two
            // explicit names were written to kill, reintroduced one level up,
            // and it is why these two variables used to be assigned and never
            // read. They are read here now.
            [$typeAttr, $idAttr] = self::BELONGS_TO_PEER_SOURCE[$childClass];
            $child = new $childClass([
                'account_id' => self::ACCOUNT,
                'id' => 5,
                'peer_type' => 9,
                'peer_id' => 8888,
                'message_peer_type' => 3,
                'message_peer_id' => -1001234567890,
            ]);

            $childAttributes = $child->getAttributes();
            foreach ([$typeAttr, $idAttr] as $sourceAttr) {
                self::assertArrayHasKey(
                    $sourceAttr,
                    $childAttributes,
                    sprintf(
                        '%s::%s() reads the enclosing message\'s peer scope from `$this->%s`, so BELONGS_TO_PEER_SOURCE '
                        .'names a REAL ATTRIBUTE of that model — and this model declares [%s]. A name that is not a real '
                        .'attribute resolves to null instead of raising, which would make the expectation below compare '
                        .'null against the leg value and report a failure that has nothing to do with the relation.',
                        $childClass,
                        $method,
                        $sourceAttr,
                        implode(', ', $childAttributes === [] ? ['<no attributes>'] : array_keys($childAttributes)),
                    ),
                );
            }

            $relation = $child->{$method}();
            self::assertInstanceOf(BelongsTo::class, $relation, sprintf('%s::%s() must be a belongsTo', $childClass, $method));
            self::assertSame('id', $relation->getForeignKeyName(), sprintf('%s::%s() keeps `id` as its single relation key; the peer legs are pinned where-clauses', $childClass, $method));
            self::assertSame('id', $relation->getOwnerKeyName(), sprintf('%s::%s() must resolve the owner on the parent id column', $childClass, $method));
            self::assertSame(
                'tf_messages.id',
                (string) $relation->getQualifiedOwnerKeyName(),
                sprintf('%s::%s() must resolve the owner on tf_messages', $childClass, $method),
            );
            // The two legs are the PARENT's peer columns either way — a belongsTo
            // filters tf_messages — so what is asserted per child is the
            // ATTRIBUTE the leg VALUES are read from. Asserting the leg names
            // alone is what let the media table's wrong reading through: it
            // pins `tf_messages.peer_type` either way, so the names look right
            // while the value is the media payload's 0.
            //
            // `$typeAttr`/`$idAttr` are the guarded destructure from above, used
            // here directly: re-reading the constant into a third pair of names
            // is what let the earlier destructure sit there unread.
            //
            // `account_id` IS IN THE EXPECTATION NOW (2026-10-06). It was
            // deliberately added to `pinnedLegs()`'s filter — the docblock on
            // that reader records that, until then, every assertion here was
            // structurally BLIND to a missing tenant leg — but these two
            // expectations below were left naming only the two peer legs, so
            // widening the reader turned them into false negatives. MEASURED
            // before this correction:
            //   expected [peer_type, peer_id], actual [account_id, peer_type, peer_id]
            // Raised to the full leg set the relation really pins; sorted,
            // because a where-clause order is query state, not a contract.
            $legs = $this->pinnedLegs($relation);
            $legNames = $this->shortLegs($legs);
            sort($legNames);
            self::assertSame(
                ['account_id', 'peer_id', 'peer_type'],
                $legNames,
                sprintf(
                    '%s::%s() must pin the tenant leg AND both peer legs on the PARENT\'s columns — a peer-only '
                    .'leg constrains no tenant, and an id-only leg is not unique within an account',
                    $childClass,
                    $method,
                ),
            );
            self::assertSame(
                [
                    'account_id' => $child->account_id,
                    'peer_id' => $child->{$idAttr},
                    'peer_type' => $child->{$typeAttr},
                ],
                self::onlyPeerAndTenantValues($legs),
                sprintf(
                    '%s::%s() must read the enclosing message\'s peer scope from `$this->%s` / `$this->%s`; '
                    .'reading `%s` pins this table\'s OWN payload peer instead (0 on every ordinary row), which '
                    .'resolves to NO message at all rather than to the wrong one',
                    $childClass,
                    $method,
                    $typeAttr,
                    $idAttr,
                    $typeAttr,
                ),
            );
            self::assertSame((new $parentClass)->getTable(), $relation->getRelated()->getTable(), sprintf('%s::%s() must target TfMessage', $childClass, $method));

            $declared = $this->crossDomainKeys($child->getTable());
            self::assertCount(1, $declared, sprintf('%s must carry exactly one cross-domain FK', $child->getTable()));
            // Two peer legs, whatever this table calls them: `tf_messages_media`
            // owns `peer_type`/`peer_id` for a peer inside its own payload, so
            // the enclosing message's legs are `message_peer_type`/
            // `message_peer_id`. The suffix test is what keeps that
            // disambiguation honest without hardcoding a second spelling here.
            $peerLegs = array_values(array_filter(
                $declared[0]['columns'],
                static fn (string $c): bool => in_array($c, ['peer_type', 'peer_id'], true)
                    || str_ends_with($c, '_peer_type') || str_ends_with($c, '_peer_id'),
            ));
            self::assertCount(
                2,
                $peerLegs,
                sprintf('%s FK must constrain BOTH halves of the message peer scope — a bare id leg is not unique within an account, which is the whole defect', $child->getTable()),
            );
            self::assertContains('id', $declared[0]['columns'], sprintf('%s FK must constrain the relation key half', $child->getTable()));
            self::assertSame('tf_messages', $declared[0]['foreign_table'], sprintf('%s cross-domain FK must name tf_messages as its parent', $child->getTable()));
        }
    }

    public function test_no_dangling_relation_targets(): void
    {
        foreach (self::CROSS_DOMAIN_FKS as $fk) {
            self::assertTrue(Schema::hasTable($fk['child']), "{$fk['child']} must exist in the migrated dial");
            self::assertTrue(Schema::hasTable($fk['parent']), "{$fk['parent']} must exist in the migrated dial");
        }

        $message = new TfMessage(['account_id' => self::ACCOUNT, 'id' => 5, 'constructor' => 'message']);
        self::assertInstanceOf(TfMessageMedia::class, $message->media()->getRelated());
        self::assertInstanceOf(TfMessageEntity::class, $message->entities()->getRelated());

        $media = new TfMessageMedia(['account_id' => self::ACCOUNT, 'id' => 5]);
        $entity = new TfMessageEntity(['account_id' => self::ACCOUNT, 'id' => 5]);
        self::assertInstanceOf(TfMessage::class, $media->message()->getRelated());
        self::assertInstanceOf(TfMessage::class, $entity->message()->getRelated());
    }

    public function test_the_media_relation_resolves_to_its_seeded_message(): void
    {
        // THE ARM THAT WAS MISSING. The loop above compares the relation's
        // pinned legs to a CONSTANT, which proves the relation was BUILT with
        // the right columns and nothing about whether it finds anything. For
        // `TfMessageMedia` that gap hid a total failure: pinning the payload
        // peer (`peer_type`/`peer_id`, both 0 on an ordinary media row) meant
        // `$media->message` resolved to NULL for every real row in the table.
        // Nothing raised, no FK was violated, and the constant matched.
        //
        // So this resolves the relation against REAL rows, and does the only
        // thing a count cannot: two dialogs holding the SAME message id, with
        // the media relation asked for the right one.
        foreach ([[1, 501558149], [3, -1001234567890]] as [$peerType, $peerId]) {
            DB::table('tf_messages')->insert([
                'account_id' => self::ACCOUNT,
                'id' => 5,
                'peer_type' => $peerType,
                'peer_id' => $peerId,
                'constructor' => 'message',
                'date' => 1726000000,
                'message' => "dialog {$peerId}",
            ]);
            // The media payload carries NO peer, so `peer_type`/`peer_id` stay
            // at their 0 defaults — exactly the shape that made the old
            // short-name predicate miss.
            DB::table('tf_messages_media')->insert([
                'account_id' => self::ACCOUNT,
                'message_peer_type' => $peerType,
                'message_peer_id' => $peerId,
                'id' => 5,
                'constructor' => 'messageMediaEmpty',
            ]);
        }

        $media = TfMessageMedia::query()
            ->where('message_peer_type', 3)->where('message_peer_id', -1001234567890)
            ->firstOrFail();

        $message = $media->message;
        self::assertInstanceOf(TfMessage::class, $message, sprintf(
            'TfMessageMedia::message() must RESOLVE against a real row; it returned null because it pinned '
            .'`peer_type`/`peer_id` — the media PAYLOAD peer, 0 on every ordinary media row — instead of the '
            .'enclosing message\'s `message_peer_*` pair',
        ));
        self::assertSame(-1001234567890, $message->peer_id, 'it must resolve ITS OWN dialog\'s message, not another dialog\'s message with the same id');
        self::assertSame(3, $message->peer_type);
        self::assertSame('dialog -1001234567890', $message->message, 'the resolved row must be the message that actually owns this media row');

        $entity = TfMessageEntity::query()
            ->where('peer_type', 1)->where('peer_id', 501558149)->where('id', 5)->where('position', 0);
        DB::table('tf_messages_entities')->insert([
            'account_id' => self::ACCOUNT,
            'peer_type' => 1,
            'peer_id' => 501558149,
            'id' => 5,
            'position' => 0,
            'constructor' => 'messageEntityBold',
            'offset' => 0,
            'length' => 1,
        ]);
        $resolvedEntity = $entity->firstOrFail()->message;
        self::assertInstanceOf(TfMessage::class, $resolvedEntity, 'TfMessageEntity::message() must resolve too — the unprefixed legs are correct FOR THAT TABLE');
        self::assertSame(501558149, $resolvedEntity->peer_id);

        // And the negative direction: a media row must not fall back to another
        // dialog's message with the same id. Pinning the wrong pair made it
        // resolve to NOTHING; a bare id leg would make it resolve to the WRONG
        // one. Both are wrong, so both are ruled out.
        $wrongDialog = TfMessageMedia::query()
            ->where('message_peer_type', 3)->where('message_peer_id', -1001234567890)
            ->firstOrFail();
        $wrongDialog->peer_id = 501558149; // the OTHER dialog's peer, as a payload peer would be
        $wrongDialog->peer_type = 1;
        self::assertSame(-1001234567890, $wrongDialog->message?->peer_id, 'the payload peer must not influence which message the relation resolves');
    }

    public function test_orphan_media_without_message_is_rejected(): void
    {
        $this->expectException(QueryException::class);
        DB::table('tf_messages_media')->insert([
            'account_id' => self::ACCOUNT,
            'message_peer_type' => 1,
            'message_peer_id' => 501558149,
            'id' => 404,
            'constructor' => 'messageMediaEmpty',
        ]);
    }

    public function test_orphan_entity_without_message_is_rejected(): void
    {
        $this->expectException(QueryException::class);
        DB::table('tf_messages_entities')->insert([
            'account_id' => self::ACCOUNT,
            'peer_type' => 1,
            'peer_id' => 501558149,
            'id' => 404,
            'position' => 0,
            'constructor' => 'messageEntityBold',
            'offset' => 0,
            'length' => 1,
        ]);
    }

    public function test_message_delete_cascades_to_media_and_entity_children(): void
    {
        DB::table('tf_messages')->insert([
            'account_id' => self::ACCOUNT,
            'id' => 77,
            'peer_type' => 1,
            'peer_id' => 501558149,
            'constructor' => 'message',
            'date' => 1726000000,
            'message' => 'cascade me',
        ]);
        // FIXTURE CORRECTION (2026-10-03, Task 2.2): every message child row
        // now carries the enclosing message's peer, because `tf_messages` and
        // its children are keyed (account_id, peer_type, peer_id, id[...]) —
        // Telegram numbers messages per chat, so `id` alone names no message.
        // The values are the ones the real writer copies out of the parent row.
        // Assertions below are unchanged.
        DB::table('tf_messages_media')->insert([
            'account_id' => self::ACCOUNT,
            'message_peer_type' => 1,
            'message_peer_id' => 501558149,
            'id' => 77,
            'constructor' => 'messageMediaEmpty',
        ]);
        DB::table('tf_messages_entities')->insert([
            'account_id' => self::ACCOUNT,
            'peer_type' => 1,
            'peer_id' => 501558149,
            'id' => 77,
            'position' => 0,
            'constructor' => 'messageEntityBold',
            'offset' => 0,
            'length' => 1,
        ]);
        DB::table('tf_messages_entities')->insert([
            'account_id' => self::ACCOUNT,
            'peer_type' => 1,
            'peer_id' => 501558149,
            'id' => 77,
            'position' => 1,
            'constructor' => 'messageEntityItalic',
            'offset' => 2,
            'length' => 3,
        ]);

        self::assertSame(1, DB::table('tf_messages_media')->count());
        self::assertSame(2, DB::table('tf_messages_entities')->count());

        DB::table('tf_messages')->where('account_id', self::ACCOUNT)->where('id', 77)->delete();

        self::assertSame(0, DB::table('tf_messages')->count());
        self::assertSame(0, DB::table('tf_messages_media')->count(), 'media must cascade with its message');
        self::assertSame(0, DB::table('tf_messages_entities')->count(), 'entities must cascade with their message');
    }

    public function test_orphan_button_without_markup_row_is_rejected(): void
    {
        $this->expectException(QueryException::class);
        DB::table('tf_messages_reply_markup_rows_buttons')->insert([
            'account_id' => self::ACCOUNT,
            'peer_type' => 1,
            'peer_id' => 501558149,
            'id' => 404,
            'position' => 0,
            'parent_position' => 0,
            'constructor' => 'keyboardButton',
        ]);
    }

    public function test_markup_row_delete_cascades_to_its_buttons(): void
    {
        // FIXTURE CORRECTION (2026-10-03, Task 2.2): every message child row
        // now carries the enclosing message's peer, because `tf_messages` and
        // its children are keyed (account_id, peer_type, peer_id, id[...]) —
        // Telegram numbers messages per chat, so `id` alone names no message.
        // The values are the ones the real writer copies out of the parent row.
        //
        // PARENT-FIRST FIXTURE (2026-10-06): the reply-markup chain is declared
        // parent-first, and BOTH links were MEASURED off the migrated schema
        // (neither is in the curated dial's hand-authored create — both are
        // derived by `2026_10_04_000800`):
        //
        //   tf_messages_reply_markup_rows (account_id,peer_type,peer_id,id)
        //     -> tf_messages_reply_markup (account_id,peer_type,peer_id,id)  cascade
        //   tf_messages_reply_markup (account_id,peer_type,peer_id,id)
        //     -> tf_messages            (account_id,peer_type,peer_id,id)  cascade
        //
        // so the rows this test plants need BOTH ancestors, or they are refused
        // at the DATABASE for reasons that have nothing to do with the cascade
        // under test.
        DB::table('tf_messages')->insert([
            'account_id' => self::ACCOUNT,
            'id' => 91,
            'peer_type' => 1,
            'peer_id' => 501558149,
            'constructor' => 'message',
            'date' => 1726000000,
            'message' => 'keyboard host',
        ]);
        DB::table('tf_messages_reply_markup')->insert([
            'account_id' => self::ACCOUNT,
            'peer_type' => 1,
            'peer_id' => 501558149,
            'id' => 91,
            'constructor' => 'replyKeyboardMarkup',
        ]);
        DB::table('tf_messages_reply_markup_rows')->insert([
            'account_id' => self::ACCOUNT,
            'peer_type' => 1,
            'peer_id' => 501558149,
            'id' => 91,
            'position' => 0,
            'constructor' => 'keyboardButtonRow',
        ]);
        DB::table('tf_messages_reply_markup_rows')->insert([
            'account_id' => self::ACCOUNT,
            'peer_type' => 1,
            'peer_id' => 501558149,
            'id' => 91,
            'position' => 1,
            'constructor' => 'keyboardButtonRow',
        ]);
        // Both rows carry a lone button at position 0 — the ordinary
        // one-button-per-row keyboard, which is the shape the composite primary
        // key on the buttons table exists to survive. Under the widened key both
        // coexist; the FK must not have narrowed it back.
        DB::table('tf_messages_reply_markup_rows_buttons')->insert([
            'account_id' => self::ACCOUNT,
            'peer_type' => 1,
            'peer_id' => 501558149,
            'id' => 91,
            'position' => 0,
            'parent_position' => 0,
            'constructor' => 'keyboardButton',
            'text' => 'first row',
        ]);
        DB::table('tf_messages_reply_markup_rows_buttons')->insert([
            'account_id' => self::ACCOUNT,
            'peer_type' => 1,
            'peer_id' => 501558149,
            'id' => 91,
            'position' => 0,
            'parent_position' => 1,
            'constructor' => 'keyboardButton',
            'text' => 'second row',
        ]);

        self::assertSame(2, DB::table('tf_messages_reply_markup_rows')->count());
        self::assertSame(2, DB::table('tf_messages_reply_markup_rows_buttons')->count(), 'one-button-per-row keyboards must coexist, not overwrite');

        DB::table('tf_messages_reply_markup_rows')
            ->where('account_id', self::ACCOUNT)
            ->where('id', 91)
            ->where('position', 0)
            ->delete();

        self::assertSame(1, DB::table('tf_messages_reply_markup_rows')->count());
        self::assertSame(1, DB::table('tf_messages_reply_markup_rows_buttons')->count(), 'only the deleted row\'s button may cascade away');

        $survivor = DB::table('tf_messages_reply_markup_rows_buttons')->first();
        self::assertNotNull($survivor);
        self::assertSame(1, (int) $survivor->parent_position);
        self::assertSame('second row', $survivor->text);
    }

    public function test_participant_orphan_without_channel_is_rejected(): void
    {
        $this->insertAccount();

        $this->expectException(QueryException::class);
        DB::table('tf_channel_participants')->insert([
            // The TENANT key, not the vault row id, so the ONLY constraint this
            // row can violate is the channel one this test is about. Left on
            // `self::ACCOUNT` it was refused by the tenant FK instead and
            // `expectException` could not tell the two apart — a green test
            // that proved nothing about channels.
            'account_id' => self::ACCOUNT_TENANT,
            'channel_id' => -100777,
            'user_id' => 501558149,
            'constructor' => 'channelParticipant',
            'date' => 1726000000,
        ]);
    }

    public function test_channel_delete_is_restricted_while_participants_exist(): void
    {
        $this->insertAccount();

        $channelId = -1002000111;

        // WHY THE DECOY CHANNEL IS GONE, AND WHAT REPLACES IT (2026-10-06)
        //
        // This test used to plant a "decoy" channel at `(self::ACCOUNT,
        // $decoyChannelId)` so that the channel leg would be SATISFIED and the
        // tenant FK would be the only constraint left to refuse the
        // participant. That fixture is now GEOMETRICALLY IMPOSSIBLE, and the
        // schema says why: the channel leg and the tenant leg are the SAME
        // COLUMN. `tf_channel_participants (account_id, channel_id) ->
        // tf_channels (account_id, id)` requires a `tf_channels` row at the same
        // `account_id`, and `tf_channels` carries the same tenant key
        // `(account_id) -> telegram_accounts(user_id)` with CASCADE — so a
        // decoy channel at `account_id = 41` can only exist while
        // `user_id = 41` exists, and while it exists the participant under
        // `account_id = 41` is ACCEPTED. There is no third state.
        //
        // MEASURED, twice, both ends:
        //   decoy planted AFTER retiring `user_id = 41` -> the DECOY insert is
        //     refused (`tf_channels (41, -1002000222)`, 19 FK violations);
        //   decoy planted BEFORE, retirement after -> the decoy is CASCADE-
        //     deleted with its tenant, so the assertion below saw 0 rows.
        //
        // So the discrimination is now made where it is actually decidable, and
        // nothing is given up:
        //
        //   1. the channel under `account_id = 41` is asserted UNREACHABLE —
        //      which is what stops a future schema change from silently
        //      restoring the possibility without anyone noticing the bite went
        //      back to being ambiguous;
        //   2. the participant under `account_id = 41` is asserted REFUSED
        //      (unchanged `self::fail()` bite);
        //   3. the tenant leg's TARGET COLUMN is asserted in the schema, by
        //      `test_every_promised_cross_domain_fk_exists_in_the_migrated_schema`,
        //      which pins `(account_id) -> telegram_accounts(USER_ID)` — the
        //      claim the decoy existed only to demonstrate behaviourally.
        //
        // Together those are strictly more than the decoy: the decoy could only
        // ever have shown that ONE configuration refuses; these pin the target
        // column itself, which is what "retargeted back onto `id`" would break.
        //
        // The reachability probe comes AFTER the retirement below, because the
        // question is "can a channel exist at (41, …) with no `user_id = 41`
        // tenant" — asked while the tenant still exists, the answer is trivially
        // yes and the assertion fires on a correct schema. MEASURED: that
        // ordering failed with exactly the message below on a schema that is
        // right.
        $decoyChannelId = -1002000222;

        // THE BITE NEEDS A TENANT STATE NO OTHER TEST IN THIS CLASS HAS
        // (2026-10-06). The claim under test is that a `tf_*` row naming
        // `telegram_accounts.ID` is refused while one naming `.USER_ID` is
        // accepted — so the vault must hold a row named `id = 41` (which
        // `insertAccount()` just planted) and NO row named `user_id = 41`.
        //
        // This class declares `self::ACCOUNT` as a tenant because most of its
        // tests write under 41, and `seedIngestTenant()` plants `user_id = 41`
        // for them. That plant is exactly what defuses the bite: with a
        // `user_id = 41` row present, `account_id => 41` resolves to it and the
        // participant insert is ACCEPTED, which is what happened — MEASURED, the
        // test died on its own `self::fail(...)` line with
        // "tf_channel_participants.account_id must be refused when it names
        // telegram_accounts.id instead of .user_id".
        //
        // So the discriminating row is removed HERE, in the one test whose
        // subject is the difference between `id` and `user_id`. This is a
        // fixture action, not a relaxation: the refusal assertion below is
        // unchanged and still has to catch a constraint retargeted back onto
        // `id`.
        $retired = DB::table('telegram_accounts')->where('user_id', self::ACCOUNT)->delete();
        self::assertSame(1, $retired, 'the class-level tenant plant must be exactly one row, or this test no longer discriminates id from user_id');
        self::assertSame(0, DB::table('telegram_accounts')->where('user_id', self::ACCOUNT)->count());
        self::assertSame(1, DB::table('telegram_accounts')->where('id', self::ACCOUNT)->count(), 'the vault row named by ID must still exist — that is what makes the refusal informative');

        // The reachability probe, asked in the state that matters: no
        // `user_id = 41` tenant. A channel CANNOT be planted there, which is
        // what makes the decoy arm unreachable — see the block above.
        try {
            DB::table('tf_channels')->insert([
                'account_id' => self::ACCOUNT,
                'id' => $decoyChannelId,
                'constructor' => 'channel',
                'title' => 'Decoy',
            ]);
            self::fail(sprintf(
                'a channel became plantable at (account_id=%d) with no `user_id = %d` tenant — the tenant leg is no '
                .'longer the only thing refusing a row there, so the participant refusal below can no longer be '
                .'attributed to tenancy. Restore the decoy-channel bite: plant the channel while the tenant exists, '
                .'then retire the tenant and let the participant be refused.',
                self::ACCOUNT,
                self::ACCOUNT,
            ));
        } catch (QueryException) {
            // The tenant FK holds on `tf_channels` too — expected, and it is
            // precisely why the decoy arm is unreachable.
        }

        try {
            DB::table('tf_channel_participants')->insert([
                'account_id' => self::ACCOUNT,
                'channel_id' => $decoyChannelId,
                'user_id' => 501558149,
                'constructor' => 'channelParticipant',
                'date' => 1726000000,
            ]);
            self::fail('tf_channel_participants.account_id must be refused when it names telegram_accounts.id instead of .user_id');
        } catch (QueryException) {
            // The tenant FK holds — expected.
        }

        // The channel carries the TENANT key, and so must its participant: the
        // channel leg is (account_id, channel_id), so the two rows only name
        // each other when their account_id agrees.
        DB::table('tf_channels')->insert([
            'account_id' => self::ACCOUNT_TENANT,
            'id' => $channelId,
            'constructor' => 'channel',
            'title' => 'Harbor Radio',
        ]);
        DB::table('tf_channel_participants')->insert([
            'account_id' => self::ACCOUNT_TENANT,
            'channel_id' => $channelId,
            'user_id' => 501558149,
            'constructor' => 'channelParticipant',
            'date' => 1726000000,
        ]);

        try {
            DB::table('tf_channels')->where('account_id', self::ACCOUNT_TENANT)->where('id', $channelId)->delete();
            self::fail('deleting a channel that still has participants must be rejected');
        } catch (QueryException) {
            // RESTRICT holds — expected.
        }

        DB::table('tf_channel_participants')
            ->where('account_id', self::ACCOUNT_TENANT)
            ->where('channel_id', $channelId)
            ->delete();
        DB::table('tf_channels')->where('account_id', self::ACCOUNT_TENANT)->where('id', $channelId)->delete();

        self::assertSame(0, DB::table('tf_channels')->count());
        self::assertSame(0, DB::table('tf_channel_participants')->count());
    }

    public function test_participant_and_channel_model_are_dial_present(): void
    {
        $participant = new TfChannelParticipant([
            'account_id' => self::ACCOUNT,
            'channel_id' => -1,
            'user_id' => 1,
            'constructor' => 'channelParticipant',
        ]);
        self::assertSame('tf_channel_participants', $participant->getTable());
        self::assertSame('tf_channels', (new TfChannel)->getTable());
        self::assertTrue(Schema::hasTable('tf_channels'), 'the canonical peer table the FK leans on must ship');
    }

    private function insertAccount(): void
    {
        DB::table('telegram_accounts')->insert([
            'id' => self::ACCOUNT,
            // REQUIRED since 983e473b: the six tenant constraints reference
            // `user_id`, so a parent row without one is not a parent of any
            // `tf_*.account_id` and every child insert below is refused. It is
            // set to a value DIFFERENT from `id` so the fixture proves which
            // column the constraint names.
            'user_id' => self::ACCOUNT_TENANT,
            'label' => 'relation-integrity-'.self::ACCOUNT,
            'type' => 'user',
            'dc_id' => 2,
        ]);
    }

    /**
     * The peer legs a built relation actually carries, read back off the
     * relation's OWN query — leg COLUMN to bound VALUE.
     *
     * READ BACK OFF THE RELATION RATHER THAN RESTATED: the point of these
     * assertions is that the ORM side and the declared foreign key cannot drift
     * apart, so a constant in this test proving the test's own constant would
     * prove nothing. `ResolvesMessagePeerScope` adds the legs as ordinary
     * `where()` clauses because Eloquent 11 takes relation keys as strings and
     * cannot express a composite key (see that trait for the measured
     * TypeError), which means the legs are query state, not key metadata.
     *
     * The VALUE is returned alongside the column because the column name alone
     * CANNOT tell the two conventions apart: a `belongsTo` filters
     * `tf_messages`, so both the media table and the entity table pin
     * `tf_messages.peer_type` — and only the value differs, which is precisely
     * what `TfMessageMedia::message()` got wrong. A name-only reader is what
     * made that defect invisible here.
     *
     * NOT delegated to the trait. The trait has no such reader: it cannot
     * have one, because a trait method is `private` to each model that uses it
     * and this is a different class. This reader is deliberately the WIDER of
     * the two — it accepts `message_peer_type`/`message_peer_id` too, which
     * the trait's own two-column filter would have thrown away.
     *
     * ## `account_id` IS IN THE FILTER NOW, AND ITS ABSENCE WAS THE DEFECT
     *
     * Until 2026-10-06 this reader's `$wanted` list held the two peer columns
     * and the two prefixed ones — and NOT `account_id`. Every assertion using it
     * was therefore structurally BLIND to the missing tenant leg, and could not
     * have failed over it however wrong the relation was. That is not a
     * hypothetical: the relation omitted `account_id` while the declared foreign
     * key is `(account_id, peer_type, peer_id, id)`, and this file's assertions
     * passed on the three-leg answer because three is all they were able to see.
     *
     * A reader whose filter decides what a test CAN look at is load-bearing, not
     * a convenience — narrowing it silently narrows the test. `account_id` is
     * added here for exactly that reason, and the assertions above now require it.
     *
     * @param  mixed  $relation
     * @return array<string, mixed>
     */
    private function pinnedLegs($relation): array
    {
        $wanted = ['account_id', 'peer_type', 'peer_id', 'message_peer_type', 'message_peer_id'];
        $legs = [];
        foreach ($relation->getQuery()->getQuery()->wheres as $where) {
            $column = $where['column'] ?? null;
            if (! is_string($column)) {
                continue;
            }
            // Compare on the BARE column: a leg may be written qualified
            // (`tf_messages.peer_type`) so that it unambiguously names the
            // PARENT's column, and that is the same claim as the bare spelling.
            $bare = str_contains($column, '.') ? substr($column, strrpos($column, '.') + 1) : $column;
            if (! in_array($bare, $wanted, true)) {
                continue;
            }
            $legs[$bare] = $where['value'] ?? null;
        }

        return $legs;
    }

    /**
     * The leg NAMES a built relation carries, table-qualified names collapsed
     * to their bare column — so a leg written `tf_messages.peer_type` and one
     * written `peer_type` are the same claim and compare equal.
     *
     * @param  array<string, mixed>  $legs
     * @return list<string>
     */
    private function shortLegs(array $legs): array
    {
        return array_keys($legs);
    }

    /**
     * The pinned legs reduced to the ones a value assertion is about — the
     * tenant leg and the two peer legs — keyed and ordered by COLUMN NAME.
     *
     * Two narrowings happen here, both deliberate. The leg SET is narrowed so
     * the assertion says what it says it says (see `onlyPeerAndTenantLegs` for
     * why that matters), and the ORDER is normalised because a where-clause
     * order is query state rather than a contract: `assertSame` on arrays is
     * order-sensitive, so comparing raw `$legs` would fail on a relation that
     * pins exactly the right legs in a different sequence.
     *
     * @param  array<string, mixed>  $legs
     * @return array<string, mixed>
     */
    private function onlyPeerAndTenantValues(array $legs): array
    {
        $kept = array_intersect_key($legs, array_flip(['account_id', 'peer_type', 'peer_id']));
        ksort($kept);

        return $kept;
    }

    /**
     * The pinned legs reduced to the ones this assertion is about: the tenant
     * leg and the two peer legs.
     *
     * A two-column KEY equality also lands in the query's `wheres` — the parent's
     * local key is joined against the child's foreign key as `local = foreign` —
     * so a raw list of every pinned column also contains that leg, which is not
     * a claim this assertion makes. Narrowing to the three columns under test
     * keeps the assertion about what it says it is about, and keeps the
     * `assertSame` order-independent by sorting.
     *
     * @param  list<string>  $legs
     * @return list<string>
     */
    private function onlyPeerAndTenantLegs(array $legs): array
    {
        $kept = [];
        foreach ($legs as $leg) {
            if (in_array($leg, ['account_id', 'peer_type', 'peer_id'], true)) {
                $kept[] = $leg;
            }
        }
        sort($kept);

        return $kept;
    }

    /**
     * The declared cross-domain FOREIGN KEYS on a table, read from the BUILT
     * schema — never from a transcription of the migration.
     *
     * ## `getForeignKeys()` RETURNS THE TENANT KEY TOO, AND A COUNT MUST NOT
     *
     * Since `2026_10_04_000700` every tenant-bearing `tf_*` table also carries
     * `(account_id) -> telegram_accounts(user_id)`, so on this table the raw
     * list has TWO entries — MEASURED off the migrated schema:
     *
     *   tf_messages_reply_markup_rows_buttons
     *     (account_id)                                                    -> telegram_accounts(user_id)          cascade
     *     (account_id,peer_type,peer_id,id,parent_position)              -> tf_messages_reply_markup_rows(...) cascade
     *
     * `assertCount(1, $this->foreignKeys($table))` therefore failed with
     * "actual size 2 matches expected size 1" against a schema that is
     * CORRECT — and the same latent failure sat on the media/entity loop below.
     * The count's subject is the CROSS-DOMAIN key (that is what its message says
     * and what `2026_09_14_299999` promises), so the tenant key is partitioned
     * off here instead of the expectation being raised to 2, which would have
     * accepted a table carrying NO cross-domain key at all.
     *
     * The partition is on the REFERENCED TABLE, not on the constraint name:
     * `Schema::getForeignKeys()` reads names off the sqlite schema, where they
     * come back empty, so a name-based split cannot be written at all on the
     * dialect these tests actually run on.
     *
     * @return list<array{columns:list<string>, foreign_table:string, foreign_columns:list<string>, on_delete:string}>
     */
    private function crossDomainKeys(string $table): array
    {
        return array_values(array_filter(
            $this->foreignKeys($table),
            static fn (array $fk): bool => $fk['foreign_table'] !== 'telegram_accounts',
        ));
    }

    /**
     * The declared foreign keys on a table, read from the BUILT
     * schema — never from a transcription of the migration.
     *
     * `foreign_columns` is carried because the tenant key's WHOLE claim is that
     * it names `telegram_accounts.USER_ID` and not `.id` (D-005, and
     * `insertAccount()` below exists to keep that claim discriminating) — which
     * cannot be checked from the referencing columns alone.
     *
     * @return list<array{columns:list<string>, foreign_table:string, foreign_columns:list<string>, on_delete:string}>
     */
    private function foreignKeys(string $table): array
    {
        return array_map(
            static fn (array $fk): array => [
                'columns' => $fk['columns'],
                'foreign_table' => (string) $fk['foreign_table'],
                'foreign_columns' => (array) $fk['foreign_columns'],
                'on_delete' => (string) $fk['on_delete'],
            ],
            Schema::getForeignKeys($table),
        );
    }
}
