<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\Assert;
use ReflectionClass;

/**
 * The `tf_channel_participants` + `tf_channel_updates` family is constrained to
 * its parents, and the database says so.
 *
 * ## WHAT IS BEING PROVED
 *
 * `2026_10_04_000860_add_tf_channel_participants_family_foreign_keys.php` adds a
 * FOREIGN KEY from every single-parent relation inside this family to that
 * parent's key. This file proves the constraints BITE — that a valid child row
 * lands, that an orphan is refused by the constraint and not by anything else,
 * and that the whole thing disappears when the migration does.
 *
 * ## WHY THE FAMILY HAS TWO ROOTS AND NOT ONE
 *
 * `tf_messages` is a prefix family: one root and everything named after it.
 * This one is not. The two roots share a first segment (`tf_channel_`) and then
 * diverge (`participants` / `updates`), and nothing in Telegram makes one a
 * descendant of the other. So membership is decided by TWO prefixes
 * (`self::FAMILY_PREFIXES`) rather than one, and the ancestry walk below tries
 * both. Written as a single root this family would silently lose all three
 * `tf_channel_updates_*` relations — or all six participant ones.
 *
 * ## THE TWO JOIN SHAPES, AND WHY BOTH ARE THE PARENT'S OWN KEY
 *
 * Measured off the executed DDL (see `test_both_join_shapes_are_the_parents_own_primary_key`):
 *
 *   tf_channel_participants  PRIMARY KEY (account_id, channel_id, user_id)
 *   tf_channel_updates      PRIMARY KEY (account_id, channel_id, position)
 *
 * Every child in each half carries exactly that key as ITS OWN primary key, so
 * the derived leg set IS the parent's key — equal, not a subset that happens to
 * be unique. This is the opposite of the `tf_messages` hazard described in
 * `2026_10_04_000800`, where a child's key is a strict SUPERSET of its parent's
 * and the legs must be narrowed down. There is nothing to narrow here and no
 * MySQL 6125 risk, and — the rule that matters — no reason to touch a single
 * primary key. Rule 2 of the plan forbids widening or narrowing a key to make
 * a constraint fit; this family satisfies every constraint WITHOUT a key change,
 * which is asserted below rather than assumed.
 *
 * ## WHY THE DISCOVERY IS RE-IMPLEMENTED HERE, ON PURPOSE
 *
 * Two implementations of one discovery is not duplication to be removed; it is
 * the only way the migration's discovery is CHECKED rather than trusted. This
 * file re-derives the family relations from the BUILT schema by its own code —
 * longest-prefix ancestry whose parent's primary key is a subset of the child's,
 * intersected down to the columns the child actually has — and asserts the
 * result against the executed DDL. If this and the migration's
 * `familyRelations()` ever disagree, one of the assertions below goes red naming
 * the difference. No table list and no join-column list is written into this
 * file: a transcribed list is a second copy of the schema and it drifts.
 *
 * ## THE TRAP THIS FIXTURE IS BUILT AROUND: TWO OF THEM
 *
 * Every `tf_*` table in this family carries its own tenant constraint to
 * `telegram_accounts.user_id`, AND `tf_channel_participants` itself carries
 * `RESTRICT ON DELETE (account_id, channel_id) → tf_channels` from
 * `2026_09_14_299999`. So an "orphan" built naively — inventing coordinates that
 * no row carries — is refused by the WRONG constraint, and every negative
 * assertion below would be green while this migration contributed nothing.
 *
 * Every fixture therefore plants a REAL tenant, a REAL channel and a REAL parent
 * in every other respect, so the orphan-ness lives ONLY in the coordinate under
 * test. Two witness children are used, one per join shape, so neither shape is
 * proven by the other: `tf_channel_participants_rank` exercises
 * `(account_id, channel_id, user_id)` and `tf_channel_updates_dialog` exercises
 * `(account_id, channel_id, position)`.
 *
 * ## AND THE CONTROL HALF, WITHOUT WHICH THE REFUSALS PROVE NOTHING
 *
 * `test_the_orphan_is_admitted_without_this_migration()` inserts the SAME orphan
 * rows into the SAME tables on the SAME engine with exactly one file withheld
 * from the dial, and requires them to LAND. That is what makes the refusals in
 * `test_the_orphan_is_refused()` attributable to this migration rather than to
 * sqlite, to the pragma, to the tenant constraint or to the `tf_channels` link.
 */
final class TfChannelParticipantsFamilyForeignKeyTest extends RelationConstraintTestCase
{
    /**
     * The path fragment naming THIS migration's file.
     *
     * Substring, not a full name, so the control half withholds the file without
     * duplicating the date stamp the migration is renamed under.
     */
    private const MIGRATION = '2026_10_04_000860';

    /**
     * The family's TWO ROOTS. Descendants are discovered by prefix, never listed.
     *
     * @var list<string>
     */
    private const FAMILY_ROOTS = ['tf_channel_participants', 'tf_channel_updates'];

    /**
     * A descendant of a root keeps its name.
     *
     * @var list<string>
     */
    private const FAMILY_PREFIXES = ['tf_channel_participants_', 'tf_channel_updates_'];

    /**
     * MEASURED on 2026-10-05, this tree, SQLite, the shipped dial.
     *
     * Command (the relation list is DISCOVERED, never transcribed):
     *
     *     timeout -k 10 900 vendor/bin/phpunit --no-coverage \
     *       --filter test_every_single_parent_relation_has_a_real_constraint \
     *       tests/Schema/Mirror/EveryFkableRelationHasAConstraintTest.php 2>&1 \
     *       | grep -oE "tf_[a-z0-9_]+\([^)]*\) → tf_[a-z0-9_]+" | sort -u \
     *       | grep -E "→ tf_channel_participants$|→ tf_channel_updates$"
     *
     * produced 9 relations:
     *
     *   tf_channel_participants_inviter(account_id,channel_id,user_id)   → tf_channel_participants
     *   tf_channel_participants_kicked_by(account_id,channel_id,user_id)→ tf_channel_participants
     *   tf_channel_participants_peer(account_id,channel_id,user_id)      → tf_channel_participants
     *   tf_channel_participants_promoted_by(account_id,channel_id,user_id)→ tf_channel_participants
     *   tf_channel_participants_rank(account_id,channel_id,user_id)      → tf_channel_participants
     *   tf_channel_participants_subscription_until_date(account_id,channel_id,user_id) → tf_channel_participants
     *   tf_channel_updates_dialog(account_id,channel_id,position)       → tf_channel_updates
     *   tf_channel_updates_pts(account_id,channel_id,position)          → tf_channel_updates
     *   tf_channel_updates_timeout(account_id,channel_id,position)      → tf_channel_updates
     *
     * This matches the plan's table for family 6 exactly. It is pinned anyway so
     * that a silent discovery change in either direction fails here.
     *
     * 🔴 9 -> 6 on 2026-10-08, and the assertion's own failure printed the figure:
     *
     *     Failed asserting that actual size 6 matches expected size 9.
     *
     * Three tables, three relations, one each: `inviter`, `kicked_by` and
     * `promoted_by`. The TL declares `channelParticipantSelf#a9478a1a … inviter_id:long`,
     * `channelParticipantAdmin#34c3bb53 … promoted_by:long` and
     * `channelParticipantBanned#d5f0ad91 … kicked_by:long` as bare `long` on ONE
     * constructor each — no `Vector<>`, so no element ordinal, so nothing for a
     * child TABLE to hold — and each was keyed on the parent's own
     * `(account_id, channel_id, user_id)`, which is exactly the derivation's
     * single-parent case. `2026_10_08_000100_fold_channel_participant_scalars_onto_the_participant_fact`
     * retires all three (audit row C5, three rows of the same kind).
     *
     * This gate measures the FINAL dial: discovery runs against the fully migrated
     * schema, so what it reads is the end state, not a stage.
     */
    private const MEASURED_RELATIONS = 6;

    /**
     * Relations this family REFUSES to constrain.
     *
     * MEASURED: 0. Every one of the six is constrained, because every parent's
     * own key is also its child's key, so no leg set needs narrowing and no
     * primary key needs changing. (Was nine until 2026-10-08; see the
     * MEASURED_RELATIONS docblock for the three that were retired.)
     *
     * The constant exists and is asserted rather than omitted, because its
     * ABSENCE is the thing worth pinning: an unnamed or vanished refusal in this
     * family would mean a relation dropped out of the constrained set with no
     * recorded mechanical reason, which is the exact gap the anti-rot gate was
     * written to close. `test_the_refused_set_is_empty_and_the_population_is_not`
     * fails if the list ever becomes non-empty without this docblock and this
     * count being updated together.
     *
     * @var array<string, string>
     */
    private const REFUSED = [];

    /** A `telegram_accounts.user_id` this file plants before every fixture. */
    private const TENANT = 1724372757;

    /**
     * The witness child for the `(account_id, channel_id, user_id)` shape.
     *
     * `tf_channel_participants_rank` has exactly four columns, all NOT NULL,
     * three of which are the parent key and one of which is the payload. The
     * fixture therefore cannot accidentally satisfy the constraint through some
     * other declared FK, and every column the constraint names is visible in the
     * insert.
     */
    private const CHILD_PARTICIPANT = 'tf_channel_participants_rank';

    /** The witness child for the `(account_id, channel_id, position)` shape. */
    private const CHILD_UPDATE = 'tf_channel_updates_dialog';

    /** A channel every fixture plants, so the `tf_channels` RESTRICT cannot be what refuses. */
    private const CHANNEL = 777000111;

    /** Coordinates of the planted parent participant row. */
    private const PARTICIPANT = ['channel_id' => self::CHANNEL, 'user_id' => 5150];

    /** A participant that is NEVER planted — the orphan on the participant shape. */
    private const ABSENT_PARTICIPANT = ['channel_id' => self::CHANNEL, 'user_id' => 999999001];

    /** Coordinates of the planted parent channel-diff row. */
    private const UPDATE = ['channel_id' => self::CHANNEL, 'position' => 4];

    /** A channel-diff row that is NEVER planted — the orphan on the update shape. */
    private const ABSENT_UPDATE = ['channel_id' => self::CHANNEL, 'position' => 4242];

    // ------------------------------------------------------------------
    // the dial
    // ------------------------------------------------------------------

    /**
     * Every shipped migration, in apply order.
     *
     * The DIRECTORY list comes from `UpdateIngestor::migrationPaths()` — the
     * same list the ingest seam and the MySQL lane run — rather than from a glob
     * of `src/Laravel/Migrations`, because a glob here would be a second copy of
     * the dial and a second copy drifts. This migration's own file lives in that
     * directory, so if it is ever moved out of the curated dial this suite fails
     * rather than quietly testing a dial without it.
     *
     * @return list<string>
     */
    private function migrations(bool $withholdThisMigration = false): array
    {
        $files = [];
        $seen = [];

        foreach (UpdateIngestor::migrationPaths() as $path) {
            $inDir = is_dir($path) ? (glob($path.'/*.php') ?: []) : [$path];
            sort($inDir);

            foreach ($inDir as $file) {
                // `migrationPaths()` yields the curated DIRECTORY and then two
                // entity FILES that already live inside it, so a naive expansion
                // applies a file twice and dies on "table already exists".
                // Deduped by real path, keeping apply order.
                $key = realpath($file) ?: $file;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                if ($withholdThisMigration && str_contains(basename($file), self::MIGRATION)) {
                    continue;
                }

                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * A private constant READ FROM THE MIGRATION rather than transcribed here.
     *
     * A test that hardcodes the spelling it is checking cannot notice the
     * spelling change that broke the rollback, so the suffix and the refusal
     * list are read out of the file itself by reflection.
     */
    private function migrationConstant(string $name): mixed
    {
        $migration = require dirname(__DIR__, 3).'/src/Laravel/Migrations/'.self::MIGRATION
            .'_add_tf_channel_participants_family_foreign_keys.php';

        Assert::assertIsObject($migration, 'the migration file did not return an object');

        return (new ReflectionClass($migration))->getConstant($name);
    }

    // ------------------------------------------------------------------
    // discovery, re-implemented
    // ------------------------------------------------------------------

    /**
     * The family relations, discovered from the BUILT schema by this file's own
     * code — NOT by asking `MirrorRelationPlan`, which is the class the migration
     * uses and therefore the thing under test.
     *
     * The rule, independently implemented: a table's parent is the LONGEST
     * surviving name prefix that is one of the two family roots AND whose primary
     * key is a subset of this table's own; the constraint's legs are that
     * parent's key intersected down to the columns this table actually has.
     *
     * @return list<array{child:string, parent:string, columns:list<string>}>
     */
    private function familyRelations(Connection $db): array
    {
        $prefix = $db->getTablePrefix();

        $tables = [];
        foreach ($db->getSchemaBuilder()->getTableListing() as $listed) {
            $bare = str_contains($listed, '.')
                ? substr($listed, (int) strrpos($listed, '.') + 1)
                : $listed;
            if ($prefix !== '' && str_starts_with($bare, $prefix)) {
                $bare = substr($bare, strlen($prefix));
            }
            $tables[] = $bare;
        }
        sort($tables);
        $tableSet = array_flip($tables);

        $columns = [];
        $primaryKey = [];
        foreach ($tables as $table) {
            $cols = [];
            foreach ($db->getSchemaBuilder()->getColumns($table) as $column) {
                $cols[(string) $column['name']] = true;
            }
            $columns[$table] = $cols;

            // The key comes from the driver's INDEX listing, not the column rows:
            // `getColumns()` carries no key position, and a composite FOREIGN
            // KEY must be declared in the parent's key ORDER, which is not the
            // order the columns sit in the table.
            $key = [];
            foreach ($db->getSchemaBuilder()->getIndexes($table) as $index) {
                if (($index['primary'] ?? false) === true) {
                    $key = array_map('strval', $index['columns']);
                    break;
                }
            }
            $primaryKey[$table] = $key;
        }

        // The family: the two roots plus every descendant discovered by name
        // prefix. Nothing here knows which migration file created what.
        $family = [];
        foreach (self::FAMILY_ROOTS as $root) {
            $family[$root] = true;
        }
        foreach ($tables as $table) {
            foreach (self::FAMILY_PREFIXES as $familyPrefix) {
                if (str_starts_with($table, $familyPrefix)) {
                    $family[$table] = true;
                }
            }
        }

        $relations = [];
        foreach ($tables as $table) {
            // A root is a parent in this family, never a child of it. Its own
            // link upward (`tf_channel_participants → tf_channels`) belongs to a
            // different family and is that lane's business, not this file's.
            if (in_array($table, self::FAMILY_ROOTS, true)) {
                continue;
            }

            $parts = explode('_', $table);
            $parent = null;
            for ($i = count($parts) - 1; $i >= 1; $i--) {
                $candidate = implode('_', array_slice($parts, 0, $i));
                if (! isset($tableSet[$candidate], $family[$candidate])) {
                    continue;
                }
                if ($primaryKey[$candidate] === [] || array_diff($primaryKey[$candidate], $primaryKey[$table]) !== []) {
                    continue;
                }
                $parent = $candidate;
                break;
            }

            if ($parent === null) {
                continue;
            }

            $legs = [];
            foreach ($primaryKey[$parent] as $leg) {
                if (isset($columns[$table][$leg])) {
                    $legs[] = $leg;
                }
            }

            // An empty leg set is not a relation; `foreign([], …)` would be a
            // runtime error inside the migration and a meaningless assertion here.
            if ($legs === []) {
                continue;
            }

            $relations[] = ['child' => $table, 'parent' => $parent, 'columns' => $legs];
        }

        return $relations;
    }

    /**
     * @return list<string>
     */
    private function refused(): array
    {
        $refused = $this->migrationConstant('REFUSED');

        Assert::assertIsArray($refused, 'REFUSED must be an array of child => reason');
        foreach ($refused as $child => $reason) {
            Assert::assertIsString($reason, "REFUSED[{$child}] must carry the REASON, not just the name");
            Assert::assertNotSame('', trim($reason), "REFUSED[{$child}] has an empty reason");
        }

        return array_keys($refused);
    }

    // ------------------------------------------------------------------
    // fixtures
    // ------------------------------------------------------------------

    /** Plant the tenant every fixture below needs, so the tenant FK cannot be what refuses. */
    private function plantTenant(Connection $db, int $tenant): void
    {
        $db->table('telegram_accounts')->insert([
            'label' => 'probe',
            'type' => 'user',
            'user_id' => $tenant,
        ]);
    }

    /**
     * Plant the channel `tf_channel_participants` links to.
     *
     * `2026_09_14_299999` declares `(account_id, channel_id) → tf_channels`
     * `RESTRICT ON DELETE`, so without this row every participant fixture would be
     * refused by that constraint instead of by the one under test.
     */
    private function plantChannel(Connection $db, int $channel): void
    {
        $db->table('tf_channels')->insert([
            'account_id' => self::TENANT,
            'id' => $channel,
            'constructor' => 'tf_channels',
        ]);
    }

    /** @param array{channel_id:int, user_id:int} $where */
    private function plantParticipant(Connection $db, array $where): void
    {
        $db->table('tf_channel_participants')->insert([
            'account_id' => self::TENANT,
            'channel_id' => $where['channel_id'],
            'user_id' => $where['user_id'],
            'constructor' => 'channelParticipant',
            'date' => 1700000000,
        ]);
    }

    /** @param array{channel_id:int, position:int} $where */
    private function plantUpdate(Connection $db, array $where): void
    {
        $db->table('tf_channel_updates')->insert([
            'account_id' => self::TENANT,
            'channel_id' => $where['channel_id'],
            'position' => $where['position'],
            'constructor' => 'channelDifference',
        ]);
    }

    /**
     * A participant child row whose TENANT, CHANNEL and every other leg are real.
     *
     * @param  array{channel_id:int, user_id:int}  $participant
     */
    private function participantChildRow(array $participant): array
    {
        return [
            'account_id' => self::TENANT,
            'channel_id' => $participant['channel_id'],
            'user_id' => $participant['user_id'],
            'rank' => 'moderator',
        ];
    }

    /**
     * A channel-diff child row whose TENANT and CHANNEL are real.
     *
     * @param  array{channel_id:int, position:int}  $update
     */
    private function updateChildRow(array $update): array
    {
        return [
            'account_id' => self::TENANT,
            'channel_id' => $update['channel_id'],
            'position' => $update['position'],
            'peer_type' => 0,
            'peer_id' => 4242,
            'top_message' => 31,
        ];
    }

    /** The orphan on the participant shape, and the same row when the parent exists. */
    private function participantOrphanRow(): array
    {
        return $this->participantChildRow(self::ABSENT_PARTICIPANT);
    }

    /** The orphan on the channel-diff shape, and the same row when the parent exists. */
    private function updateOrphanRow(): array
    {
        return $this->updateChildRow(self::ABSENT_UPDATE);
    }

    // ------------------------------------------------------------------
    // PROOF 1 — the discovered family relations are the MEASURED ones
    // ------------------------------------------------------------------

    public function test_the_discovered_family_relations_are_the_measured_ones(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $discovered = $this->familyRelations($db);

        self::assertCount(
            self::MEASURED_RELATIONS,
            $discovered,
            'the number of single-parent relations inside the tf_channel_participants + tf_channel_updates '
            .'family moved. That is expected when the family gains or loses a table — but then this '
            .'constant AND the docblock in '.self::MIGRATION
            .'_add_tf_channel_participants_family_foreign_keys.php are both stale. Re-measure on the '
            .'target engine and bump BOTH. Never edit this count to reach a green run: it is the only '
            .'thing standing between a deleted mirror table and a silent green.',
        );

        self::assertCount(
            self::MEASURED_RELATIONS - count($this->refused()),
            array_diff(array_column($discovered, 'child'), $this->refused()),
            'the number of relations this migration actually constrains is (discovered − refused). If only '
            .'one of those two numbers moved, the constant and the REFUSED list have drifted apart.',
        );
    }

    /**
     * BOTH halves of the family are actually found — the guard against a
     * single-root reading silently dropping one of them.
     *
     * A family whose two roots share a first name segment is exactly the shape
     * where a copy-paste of the `tf_messages` discovery finds the participants
     * half and quietly misses the updates half, with no assertion anywhere
     * failing: three relations simply stop being constrained and the anti-rot gate
     * still counts them missing. This asserts each root contributes at least one
     * relation, so that failure would be named here instead.
     */
    public function test_both_roots_contribute_relations(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $byRoot = [];
        foreach ($this->familyRelations($db) as $relation) {
            $byRoot[$relation['parent']][] = $relation['child'];
        }

        foreach (self::FAMILY_ROOTS as $root) {
            self::assertNotEmpty(
                $byRoot[$root] ?? [],
                "no relation in this family resolves to {$root}. This family has TWO roots sharing the "
                ."prefix `tf_channel_`; a discovery that walks only one prefix finds the other half's "
                .'relations as if they did not exist, and no other assertion in this file notices.',
            );
        }

        self::assertSame(
            ['tf_channel_participants', 'tf_channel_updates'],
            array_keys($byRoot),
            'the family resolved to parents outside its two declared roots, so the ancestry walk is '
            .'matching something it should not. Re-read self::FAMILY_ROOTS before trusting the counts.',
        );
    }

    /**
     * RULE 2, ASSERTED: both join shapes are the parent's OWN PRIMARY KEY, so
     * this family needs no key change at all.
     *
     * This is the mechanical check behind the decision NOT to widen or narrow any
     * primary key. A widened parent with unwidened children produces children
     * whose `id` resolves to no parent — a NEW inconsistency, worse than the
     * uniform unconstrained state it replaced. So the requirement is not "the
     * legs are unique", it is "the legs are the parent's own key, and the child's
     * own key is that same set" — which is what makes the constraint safe without
     * touching a key.
     *
     * Read off the driver's index listing rather than from the migration text, so
     * this is a statement about the schema that will actually receive the ALTER.
     */
    public function test_both_join_shapes_are_the_parents_own_primary_key(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        foreach (self::FAMILY_ROOTS as $root) {
            $parentKey = $this->primaryKeyOf($db, $root);
            self::assertNotSame(
                [],
                $parentKey,
                "{$root} has no readable primary key on this engine, so nothing below is proven.",
            );

            $children = 0;
            foreach ($this->familyRelations($db) as $relation) {
                if ($relation['parent'] !== $root) {
                    continue;
                }
                $children++;

                self::assertSame(
                    $parentKey,
                    $relation['columns'],
                    "{$relation['child']} → {$root} joins over [".implode(',', $relation['columns'])
                    ."] but {$root}'s own PRIMARY KEY is [".implode(',', $parentKey)
                    .']. A FOREIGN KEY must reference a UNIQUE index on the parent, and the only one here is '
                    .'that key. If these differ then either the legs must be NARROWED (which is the '
                    .'MirrorRelationPlan defect the tf_messages family hit) or the parent key must change — '
                    .'and rule 2 of the plan forbids shipping that key change.',
                );
            }

            self::assertGreaterThan(0, $children, "no relation resolved to {$root}, so the key assertion above is vacuous.");
        }
    }

    /**
     * The legs `$child` joins to its parent over, by discovery.
     *
     * @return list<string>
     */
    private function legsOf(Connection $db, string $child): array
    {
        foreach ($this->familyRelations($db) as $relation) {
            if ($relation['child'] === $child) {
                return $relation['columns'];
            }
        }

        return [];
    }

    /**
     * Every child's own PRIMARY KEY equals the legs it joins over.
     *
     * The other half of rule 2: it is not enough that the legs are the parent's
     * key, the child must also KEY on them — otherwise the leg set is not unique
     * on the child side and the pair admits several children per parent where the
     * schema says one.
     *
     * @return list<string>
     */
    private function primaryKeyOf(Connection $db, string $table): array
    {
        foreach ($db->getSchemaBuilder()->getIndexes($table) as $index) {
            if (($index['primary'] ?? false) === true) {
                return array_map('strval', $index['columns']);
            }
        }

        return [];
    }

    // ------------------------------------------------------------------
    // PROOF 2 — the DECLARATION, on every relation the migration claims
    // ------------------------------------------------------------------

    public function test_every_family_relation_declares_its_parent_foreign_key(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $refused = $this->refused();
        $missing = [];
        $wrong = [];

        foreach ($this->familyRelations($db) as $relation) {
            if (in_array($relation['child'], $refused, true)) {
                continue;
            }

            $found = null;
            foreach ($db->getSchemaBuilder()->getForeignKeys($relation['child']) as $foreign) {
                if ((string) ($foreign['foreign_table'] ?? '') !== $relation['parent']) {
                    continue;
                }
                if (array_map('strval', $foreign['foreign_columns'] ?? []) === $relation['columns']) {
                    $found = $foreign;
                    break;
                }
            }

            if ($found === null) {
                $missing[] = "{$relation['child']} -> {$relation['parent']} [".implode(',', $relation['columns']).']';

                continue;
            }

            $rule = strtoupper((string) ($found['on_delete'] ?? ''));
            if ($rule !== 'CASCADE') {
                $wrong[] = "{$relation['child']}: on_delete={$rule}";
            }
        }

        self::assertSame(
            [],
            $missing,
            'these family relations declare NO foreign key to their parent, so a child row naming a parent '
            .'that does not exist is admitted. Delete '.self::MIGRATION
            .'_add_tf_channel_participants_family_foreign_keys.php and this is the failure you get — which '
            .'is why it is asserted, not assumed.',
        );

        self::assertSame(
            [],
            $wrong,
            'the constraint exists but does not CASCADE. Every child in this family is a 1:1 facet '
            .'decomposed from its parent row (flags bits, an inviter id, a pinned dialog), so a facet has '
            .'no life outside the row it was decomposed from; a facet outliving its parent is the state '
            .'this constraint exists to prevent.',
        );
    }

    /**
     * PROOF 2b — the REFUSED set is empty AND the population it filters is not.
     *
     * An empty refusal list is only meaningful if the set it filters is non-empty.
     * A discovery that stopped finding relations would make `REFUSED = []` look
     * like a clean sweep rather than a discovery failure, which is why both facts
     * are asserted in the same test.
     */
    public function test_the_refused_set_is_empty_and_the_population_is_not(): void
    {
        self::assertSame(
            [],
            $this->refused(),
            'this family refused a relation. That is a legitimate outcome and the reason belongs in the '
            .'migration\'s REFUSED docblock — but it is NOT the state this file was written against, and '
            .'shipping it silently would mean a relation nobody looked at stopped being constrained.',
        );

        $db = $this->enforcingConnection(...$this->migrations());
        self::assertCount(
            self::MEASURED_RELATIONS,
            $this->familyRelations($db),
            'discovery found a different population, so the empty REFUSED list above is filtering '
            .'something other than the '.self::MEASURED_RELATIONS.' relations this file names. Re-measure.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 3 — CONTROL: both orphans are ADMITTED without this migration
    // ------------------------------------------------------------------

    public function test_the_orphans_are_admitted_without_this_migration(): void
    {
        $withheld = $this->migrations(withholdThisMigration: true);

        // The control withholds exactly one file, and it must be this migration.
        // If the filter matched nothing, this control is not a control and PROOF 4
        // would be proving something other than what it claims.
        self::assertSame(
            1,
            count($this->migrations()) - count($withheld),
            'the control withholds exactly one file, and it must be '.self::MIGRATION
            .'. If the filter matched nothing, every refusal asserted elsewhere in this file is unattributable.',
        );

        // Deliberately the shipped default, NOT enforcingConnection(): the point
        // is what the dial WITHOUT this migration permits. The pragma state is
        // asserted so a future default cannot quietly change what this means.
        $permissive = ShippedStoreSchema::capsuleFor(...$withheld)
            ->getConnection();
        self::assertSame(0, $this->foreignKeysPragma($permissive));

        $this->plantTenant($permissive, self::TENANT);
        $this->plantChannel($permissive, self::CHANNEL);

        $permissive->table(self::CHILD_PARTICIPANT)->insert($this->participantOrphanRow());
        $permissive->table(self::CHILD_UPDATE)->insert($this->updateOrphanRow());

        self::assertSame(
            1,
            $permissive->table(self::CHILD_PARTICIPANT)->count(),
            'the participant-shape orphan must actually LAND without '.self::MIGRATION.'. Its tenant and '
            .'channel are real, so if this row were refused the refusal would have come from another '
            .'constraint, and PROOF 4 would be proving that instead of this migration.',
        );

        self::assertSame(
            1,
            $permissive->table(self::CHILD_UPDATE)->count(),
            'the channel-diff-shape orphan must actually LAND without '.self::MIGRATION
            .', for the same reason.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 4 — the SAME orphans are REFUSED once the migration ships
    // ------------------------------------------------------------------

    /**
     * @return list<array{0:string, 1:array<string, mixed>, 2:string}>
     */
    private function orphanFixtures(): array
    {
        return [
            [self::CHILD_PARTICIPANT, $this->participantOrphanRow(), self::ABSENT_PARTICIPANT['user_id'].'(user_id)'],
            [self::CHILD_UPDATE, $this->updateOrphanRow(), self::ABSENT_UPDATE['position'].'(position)'],
        ];
    }

    public function test_the_orphans_are_refused(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $this->plantTenant($db, self::TENANT);
        $this->plantChannel($db, self::CHANNEL);

        foreach ($this->orphanFixtures() as [$child, $row, $absent]) {
            try {
                $db->table($child)->insert($row);
                self::fail(
                    "a {$child} row naming {$absent}, which does not exist, was ACCEPTED on a connection whose "
                    .'PRAGMA foreign_keys was read back as 1. Its tenant and channel ARE valid, so neither '
                    .'the tenant constraint nor the tf_channels link can be what should have refused it — '
                    .'either this migration\'s constraint is absent, or enforcement is not on, and both mean '
                    .'this suite is not a gate.',
                );
            } catch (QueryException $e) {
                self::assertStringContainsString(
                    'FOREIGN KEY constraint failed',
                    $e->getMessage(),
                    "the {$child} insert must be refused BY THE FOREIGN KEY. Any other QueryException means "
                    .'the fixture is wrong — it is not evidence the constraint bit.',
                );
                self::assertStringContainsString(
                    '23000',
                    $e->getMessage(),
                    'sqlite reports a foreign-key refusal as SQLSTATE 23000; assert it so a different '
                    .'refusal class cannot satisfy this test.',
                );
            }

            self::assertSame(
                0,
                $db->table($child)->count(),
                "a refused insert into {$child} must leave nothing behind.",
            );
        }
    }

    // ------------------------------------------------------------------
    // PROOF 5 — real child rows LAND on BOTH shapes, and CASCADE reaches them
    // ------------------------------------------------------------------

    public function test_real_child_rows_land_on_both_shapes_and_deleting_the_parent_cascades(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $this->plantTenant($db, self::TENANT);
        $this->plantChannel($db, self::CHANNEL);
        $this->plantParticipant($db, self::PARTICIPANT);
        $this->plantUpdate($db, self::UPDATE);

        $participantRow = $this->participantChildRow(self::PARTICIPANT);
        $updateRow = $this->updateChildRow(self::UPDATE);

        $db->table(self::CHILD_PARTICIPANT)->insert($participantRow);
        $db->table(self::CHILD_UPDATE)->insert($updateRow);

        // Each shape is proven by its own row: `tf_channel_updates_dialog` must
        // NOT be satisfied by the participant constraint and vice versa, because
        // the two leg sets differ in their third column.
        $landedParticipant = $db->table(self::CHILD_PARTICIPANT)->first();
        self::assertNotNull($landedParticipant, 'the legitimate participant facet must LAND. A constraint that '
            .'refuses real engine rows is worse than no constraint: it is an outage, not a safety net.');

        $landedUpdate = $db->table(self::CHILD_UPDATE)->first();
        self::assertNotNull($landedUpdate, 'the legitimate channel-diff facet must LAND, on the other leg set.');

        // The constrained LEGS, read back as the values inserted.
        //
        // Only the legs are compared as integers. `tf_channel_participants_rank.rank`
        // is a TEXT payload, and casting it to int here made this proof read
        // `0 !== 'moderator'` and fail on a row that had in fact landed correctly —
        // a fixture that reports a real engine row as broken. So the legs come
        // from DISCOVERY (`legsOf()`, the same list the constraint is built over),
        // and the payload is compared as the string it is.
        $participantLegs = $this->legsOf($db, self::CHILD_PARTICIPANT);
        self::assertNotSame([], $participantLegs, 'no legs were discovered for '.self::CHILD_PARTICIPANT
            .', so the read-back below proves nothing.');

        foreach ($participantLegs as $leg) {
            self::assertSame(
                $participantRow[$leg],
                (int) $landedParticipant->{$leg},
                "the constrained leg {$leg} must come back as the value inserted on "
                .self::CHILD_PARTICIPANT.'; a mismatch would mean the constraint is over a different column '
                .'set than the one this fixture built.',
            );
        }

        self::assertSame(
            $participantRow['rank'],
            (string) $landedParticipant->rank,
            'the TEXT payload must come back verbatim on '.self::CHILD_PARTICIPANT
            .'. This facet is 1:1 with its parent, so a payload that does not survive the round trip means '
            .'the row that landed is not the row that was inserted.',
        );

        $updateLegs = $this->legsOf($db, self::CHILD_UPDATE);
        self::assertNotSame([], $updateLegs, 'no legs were discovered for '.self::CHILD_UPDATE
            .', so the read-back below proves nothing.');

        self::assertNotSame(
            $participantLegs,
            $updateLegs,
            'both witness children resolved to the SAME leg set, so this file is proving the participant shape '
            .'twice and the channel-diff shape not at all. The two roots differ in their third column '
            .'(user_id vs position) precisely so that a filter bug cannot collapse them into one.',
        );

        foreach ($updateLegs as $leg) {
            self::assertSame(
                $updateRow[$leg],
                (int) $landedUpdate->{$leg},
                "the constrained leg {$leg} must come back as the value inserted on "
                .self::CHILD_UPDATE.'; a mismatch would mean the constraint is over a different column set '
                .'than the one this fixture built.',
            );
        }

        foreach (['peer_type', 'peer_id', 'top_message'] as $column) {
            self::assertSame(
                $updateRow[$column],
                (int) $landedUpdate->{$column},
                "the constrained facet's own column {$column} must come back as the value inserted on "
                .self::CHILD_UPDATE.'.',
            );
        }

        // CASCADE, one parent at a time, so the second delete cannot be masked by
        // a cascade that had not yet run.
        $db->table('tf_channel_participants')
            ->where('account_id', self::TENANT)
            ->where('channel_id', self::PARTICIPANT['channel_id'])
            ->where('user_id', self::PARTICIPANT['user_id'])
            ->delete();

        self::assertSame(
            0,
            $db->table(self::CHILD_PARTICIPANT)->count(),
            'deleting the parent participant must take its facets with it (ON DELETE CASCADE). A facet row '
            .'outliving the participant it was decomposed from is the state this constraint exists to '
            .'prevent.',
        );

        self::assertSame(
            1,
            $db->table(self::CHILD_UPDATE)->count(),
            'deleting a participant row must NOT have reached across to the unrelated channel-diff facet; if '
            .'this reads 0 the cascade is over-broad and this constraint is deleting live mirror data.',
        );

        $db->table('tf_channel_updates')
            ->where('account_id', self::TENANT)
            ->where('channel_id', self::UPDATE['channel_id'])
            ->where('position', self::UPDATE['position'])
            ->delete();

        self::assertSame(
            0,
            $db->table(self::CHILD_UPDATE)->count(),
            'deleting the parent channel-diff row must take its facets with it (ON DELETE CASCADE).',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 6 — every constraint NAME fits the engine's identifier budget
    // ------------------------------------------------------------------

    /**
     * MEASURED on MySQL 8.4.11: the limit is exactly 64 characters accepted, 65
     * refused with `ERROR 1059 ... Identifier name is too long`. sqlite has NO
     * identifier limit at all, so a name that is too long passes every sqlite test
     * and fails on the first real ALTER.
     *
     * The longest table in THIS family is
     * `tf_channel_participants_subscription_until_date` at 47 characters, so the
     * whole budget left for a suffix is 17 — and the suffix this file uses is 12.
     * That is asserted here rather than assumed, because the family's longest name
     * is unusually close to the ceiling and a suffix bump would go unnoticed
     * until the first real migration.
     */
    public function test_every_family_constraint_name_fits_the_engine_identifier_budget(): void
    {
        $limit = 64;

        $db = $this->enforcingConnection(...$this->migrations());
        $suffix = $this->migrationConstant('CONSTRAINT_SUFFIX');
        Assert::assertIsString($suffix);

        $longest = '';
        foreach ($this->familyRelations($db) as $relation) {
            $name = $relation['child'].$suffix;
            self::assertLessThanOrEqual(
                $limit,
                strlen($name),
                "the constraint name for {$relation['child']} is ".strlen($name).' characters, over MySQL\'s '
                ."{$limit}-character identifier limit, which it refuses with ERROR 1059. Shorten "
                .'CONSTRAINT_SUFFIX in the migration — do not shorten it here, because the test and the '
                .'migration must agree on the same spelling.',
            );
            if (strlen($relation['child']) > strlen($longest)) {
                $longest = $relation['child'];
            }
        }

        self::assertNotSame('', $longest, 'discovery found no family relations, so nothing above is proven');
        self::assertLessThanOrEqual(
            $limit,
            strlen($longest.$suffix),
            "the longest family table ({$longest}) plus the suffix is over the budget.",
        );
    }

    // ------------------------------------------------------------------
    // PROOF 7 — signedness, which sqlite cannot express
    // ------------------------------------------------------------------

    /**
     * Every constrained leg must match its parent's column SIGNEDNESS and type.
     *
     * This is the MySQL 3780 failure (`Referencing column 'x' and referenced
     * column 'y' in foreign key constraint 'f' are incompatible`), and it is the
     * one class of defect sqlite can report as green no matter what, because
     * sqlite has no signedness to disagree about. Asserting it here — against the
     * executed DDL of BOTH tables — is the only local check available, and it is
     * why the MySQL lane exists.
     *
     * This family is the case where it matters: `account_id` is declared
     * `unsignedBigInteger` on the child and the parent, but a composite leg that
     * mixed it with a SIGNED column would be green on sqlite forever.
     */
    public function test_every_constrained_leg_matches_its_parents_signedness(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $refused = $this->refused();
        $mismatch = [];
        $checked = 0;

        foreach ($this->familyRelations($db) as $relation) {
            if (in_array($relation['child'], $refused, true)) {
                continue;
            }

            $childColumns = $this->columnTypes($db, $relation['child']);
            $parentColumns = $this->columnTypes($db, $relation['parent']);

            foreach ($relation['columns'] as $leg) {
                $child = $childColumns[$leg] ?? null;
                $parent = $parentColumns[$leg] ?? null;

                if ($child === null || $parent === null) {
                    $mismatch[] = "{$relation['child']}.{$leg} or {$relation['parent']}.{$leg} does not exist";

                    continue;
                }
                $checked++;

                if ($child !== $parent) {
                    $mismatch[] = sprintf(
                        '%s.%s is [%s] but %s.%s is [%s]',
                        $relation['child'], $leg, $child,
                        $relation['parent'], $leg, $parent,
                    );
                }
            }
        }

        self::assertGreaterThan(
            0,
            $checked,
            'no leg was compared, so this proof is vacuous — discovery found no constrained relation.',
        );
        self::assertSame(
            [],
            $mismatch,
            'a constrained leg disagrees with its parent\'s column type or signedness. MySQL refuses the '
            .'ALTER with error 3780 and sqlite cannot raise it at all, so this is the one defect class a '
            .'sqlite-only suite reports as green forever.',
        );
    }

    /**
     * `column_name => "TYPE SIGNEDNESS"`, normalised so the comparison is about
     * the thing MySQL actually refuses on.
     *
     * @return array<string, string>
     */
    private function columnTypes(Connection $db, string $table): array
    {
        $types = [];
        foreach ($db->getSchemaBuilder()->getColumns($table) as $column) {
            $name = (string) $column['name'];
            $type = strtolower((string) ($column['type'] ?? ''));

            $unsigned = str_contains($type, 'unsigned');
            $base = trim(str_replace(
                ['unsigned', 'signed', 'bigint', 'mediumint', 'smallint', 'tinyint', 'integer', 'int', ''],
                ' ',
                $type,
            ));
            $types[$name] = trim($base).($unsigned ? '|UNSIGNED' : '|SIGNED');
        }

        return $types;
    }
}
