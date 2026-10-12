<?php

/**
 * Generate the mirror relation graph from the EXECUTED shipped DDL.
 *
 * WHY THIS EXISTS, and why it measures instead of grepping.
 *
 * `grep '->foreign('` UNDERCOUNTS, badly. Foreign keys reach this schema through
 * shared Blueprint helpers -- `participantLink()`, `channelUpdateLink()`,
 * `differenceLink()`, `linkChild()`, `factKey()` -- which are private static
 * methods on the migration classes themselves, so a text search for
 * `->foreign(` in `src/Laravel/Migrations/` sees only the literal inline
 * declarations and misses every constraint a helper emits. It also cannot see
 * primary keys, index shapes, or the composite column sets that decide
 * cardinality.
 *
 * So this script BOOTS the shipped migrations against an in-memory sqlite
 * connection and then REFLECTS with `PRAGMA`. The migrations are the only
 * source of truth; nothing here transcribes a table shape, and nothing here
 * parses PHP.
 *
 * Usage:
 *   php bin/generate-relation-graph.php            # write the doc
 *   php bin/generate-relation-graph.php --check    # exit 1 if the doc is stale
 *
 * Exit codes: 0 ok, 1 stale (with --check), 2 usage.
 *
 * Provenance: the measurement pattern is tests/Store/ShippedStoreSchema.php,
 * which already boots the shipped store DDL the same way. The reason that
 * class exists is the reason this script exists: a document that re-types a
 * migration's shape is a second copy of the DDL, and a second copy drifts.
 */

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorRelationPlan;
use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

// -----------------------------------------------------------------------------
// 1. Execute the shipped migrations, in filename (apply) order.
// -----------------------------------------------------------------------------

$capsule = new Capsule;
$capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$app = new Container;
$app->instance('db', $capsule->getDatabaseManager());
$app->instance('db.schema', $capsule->getConnection()->getSchemaBuilder());
Container::setInstance($app);
Facade::setFacadeApplication($app);
Facade::clearResolvedInstances();

$migrationFiles = glob($root.'/src/Laravel/Migrations/*.php') ?: [];
sort($migrationFiles);

/** @var array<string, object> $migrationClasses basename => the migration object it `require`d */
$migrationClasses = [];

foreach ($migrationFiles as $file) {
    $migration = require $file;

    if (! is_object($migration) || ! method_exists($migration, 'up')) {
        fwrite(STDERR, "not a migration: {$file}\n");
        exit(2);
    }

    $migration->up();

    // Kept so a family migration's own `REFUSED` list can be READ by reflection
    // below, rather than grepped out of its source. `require` has already
    // parsed the file; `ReflectionClass::getConstant()` reads the value the
    // class actually executes, so the reason published here is the one the
    // migration ships — a second hand-written copy of it would drift, which is
    // the whole reason this document is a script.
    $migrationClasses[basename($file)] = $migration;
}

$pdo = $capsule->getConnection()->getPdo();

// -----------------------------------------------------------------------------
// 2. Reflect. Nothing below is transcribed from a migration.
// -----------------------------------------------------------------------------

/** @var list<string> $tables every table the shipped migrations actually create */
$tables = $pdo->query(
    "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
)->fetchAll(PDO::FETCH_COLUMN);

$tableSet = array_flip($tables);

/** @var array<string, list<string>> $primaryKey */
$primaryKey = [];

/** @var array<string, list<string>> $columns */
$columns = [];

foreach ($tables as $table) {
    $primaryKey[$table] = [];
    $columns[$table] = [];

    /** @var list<array{ordinal: int, name: string}> $keyColumns */
    $keyColumns = [];

    foreach ($pdo->query("PRAGMA table_info('".$table."')")->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $name = (string) $column['name'];
        $columns[$table][] = $name;

        // `pk` IS the 1-based ORDINAL of the column WITHIN the primary key --
        // it is the key's column order, not a boolean "is in the key" flag.
        // Filtering on `> 0` and APPENDING therefore publishes the columns in
        // PRAGMA DECLARATION order and throws the ordinal away, and those are
        // two different orders: `tf_messages` declares
        // (account_id, peer_type, peer_id, id) but its columns sit in the
        // table as (account_id, …, id, …, peer_type, peer_id).
        // `tf_message_search` is the case that bites hardest — declaration
        // order publishes (account_id, token, id, peer_type, peer_id), which
        // INVERTS the "token IMMEDIATELY after account_id so the prefix range
        // scan stays contiguous" argument its own migration rests on.
        $ordinal = (int) $column['pk'];
        if ($ordinal > 0) {
            $keyColumns[] = ['ordinal' => $ordinal, 'name' => $name];
        }
    }

    usort($keyColumns, static fn (array $a, array $b): int => $a['ordinal'] <=> $b['ordinal']);

    foreach ($keyColumns as $keyColumn) {
        $primaryKey[$table][] = $keyColumn['name'];
    }
}

/**
 * Group `PRAGMA foreign_key_list` rows into constraints.
 *
 * A composite foreign key emits ONE row PER COLUMN, all sharing the same `id`
 * and differing in `seq`. Counting rows therefore counts columns, not
 * constraints -- the same class of error as grepping `->foreign(`.
 *
 * @param  list<array<string, mixed>>  $rows
 * @return list<array{table: string, from: list<string>, to: list<string>, on_delete: string}>
 */
function groupForeignKeys(array $rows): array
{
    $grouped = [];

    foreach ($rows as $row) {
        $id = (int) $row['id'];
        $grouped[$id] ??= [
            'table' => (string) $row['table'],
            'from' => [],
            'to' => [],
            'on_delete' => (string) $row['on_delete'],
        ];
        $grouped[$id]['from'][] = (string) $row['from'];
        $grouped[$id]['to'][] = (string) $row['to'];
    }

    ksort($grouped);

    return array_values($grouped);
}

/** @var array<string, list<array{table: string, from: list<string>, to: list<string>, on_delete: string}>> $foreignKeys */
$foreignKeys = [];

foreach ($tables as $table) {
    $foreignKeys[$table] = groupForeignKeys(
        $pdo->query("PRAGMA foreign_key_list('".$table."')")->fetchAll(PDO::FETCH_ASSOC)
    );
}

// -----------------------------------------------------------------------------
// 3. Derive one relation per table.
// -----------------------------------------------------------------------------

/**
 * The parent a table belongs to, and WHY.
 *
 * Three link kinds, in descending order of authority:
 *
 *  - `fk`   a FOREIGN KEY constraint to another table exists. Authoritative.
 *  - `soft` the table is a child of `{prefix}_…` whose primary key is a
 *           set-superset of -- or equal to -- this table's primary key, and
 *           no FK declares it. Joinable in SQL, enforced nowhere.
 *  - `root` no parent found by any of the above.
 *
 * A polymorphic peer reference is NOT a parent link and must not be recorded
 * as one. `tf_messages_from_id` is a child of `tf_messages` AND carries a
 * `{base}_type`/`{base}_id` peer pair; reporting the peer triad as its parent
 * would erase the real inheritance and hide the family. Polymorphism is
 * therefore a separate attribute, not a link kind.
 *
 * @param  array<string, string>  $polymorphicBase  table => the `{base}` of its first `{base}_type` pair
 * @return array{parent: ?string, join: list<string>, cardinality: string, link: string}
 */
const TENANT_PARENT = 'telegram_accounts';

function relationFor(
    string $table,
    array $foreignKeys,
    array $primaryKey,
    array $columns,
    array $tableSet,
): array {
    // --- FK: the constraint is the fact.
    $subjectForeignKeys = array_values(array_filter(
        $foreignKeys[$table],
        fn (array $fk): bool => $fk['table'] !== $table,
    ));

    if ($subjectForeignKeys !== []) {
        // DETERMINISTIC TIE-BREAK. `$subjectForeignKeys[0]` was whatever
        // `PRAGMA foreign_key_list` happened to return first, and SQLite
        // assigns `foreign_key_list.id` in REVERSE declaration order — so the
        // published parent was a function of migration declaration order
        // rather than of the schema. Demonstrated: two trees differing only by
        // an unrelated column-widening migration published DIFFERENT parents
        // for the same table (`tf_documents_thumbs` -> `telegram_accounts` with
        // 60 migrations, -> `tf_documents` with 61). An artefact that moves
        // when an unrelated file lands is not a measurement.
        //
        // Every tenant-bearing table declares `account_id ->
        // telegram_accounts`, so that constraint is the TENANT leg and never the
        // structural parent edge; 202 tables declare more than one outbound
        // constraint and on 43 of them the tenant leg won the slot. Prefer any
        // other leg, and fall back to the tenant leg for the tables whose ONLY
        // constraint is that one — there it genuinely is the parent.
        $structural = array_values(array_filter(
            $subjectForeignKeys,
            static fn (array $fk): bool => $fk['table'] !== TENANT_PARENT,
        ));
        $fk = $structural[0] ?? $subjectForeignKeys[0];
        $one = count($primaryKey[$table]) === count($fk['from'])
            && array_diff($primaryKey[$table], $fk['from']) === [];

        return [
            'parent' => $fk['table'],
            'join' => $fk['from'],
            'cardinality' => $one ? '1:1' : '1:N',
            'link' => 'fk',
        ];
    }

    // --- Soft: longest surviving table-name prefix whose PK covers this one.
    $parts = explode('_', $table);
    for ($i = count($parts) - 1; $i >= 1; $i--) {
        $candidate = implode('_', array_slice($parts, 0, $i));

        if (! isset($tableSet[$candidate])) {
            continue;
        }

        if (array_diff($primaryKey[$candidate], $primaryKey[$table]) !== []) {
            continue;
        }

        // Order-INSENSITIVE on purpose. The `array_diff` above already proved
        // the candidate's key SET covers the child's, so "same set" reduces to
        // a count comparison. A strict `===` would compare ORDER as well and
        // so report `1:N` for a pair of tables whose keys hold the same
        // columns in a different declaration order — a false 1:N, published.
        $one = count($primaryKey[$candidate]) === count($primaryKey[$table]);

        return [
            'parent' => $candidate,
            'join' => $primaryKey[$table],
            'cardinality' => $one ? '1:1' : '1:N',
            'link' => 'soft',
        ];
    }

    return ['parent' => null, 'join' => $primaryKey[$table], 'cardinality' => '—', 'link' => 'root'];
}

/**
 * Find `{base}_type` + `{base}_id` sibling pairs: a reference that branches on
 * a type int rather than naming one parent table.
 *
 * @param  list<string>  $columns
 * @return string the base name of the first pair found, '' when there is none
 */
function polymorphicBaseOf(array $columns): string
{
    $set = array_flip($columns);

    // Longest base first, so `reply_to_peer_id` wins over a shorter pair that
    // happens to sit inside its own name.
    $types = $columns;
    usort($types, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

    foreach ($types as $column) {
        if (! str_ends_with($column, '_type')) {
            continue;
        }

        $base = substr($column, 0, -strlen('_type'));

        if ($base !== '' && isset($set[$base.'_id'])) {
            return $base;
        }
    }

    return '';
}

$polymorphicBase = [];
foreach ($tables as $table) {
    $polymorphicBase[$table] = polymorphicBaseOf($columns[$table]);
}

// -----------------------------------------------------------------------------
// 4. Tally.
// -----------------------------------------------------------------------------

$mirror = array_values(array_filter($tables, fn (string $t): bool => str_starts_with($t, 'tf_')));
$mirrorSet = array_flip($mirror);

$withForeignKey = 0;
$withoutForeignKey = 0;
$constraintCount = 0;
$allConstraintCount = 0;
$linkTally = ['fk' => 0, 'soft' => 0, 'root' => 0];
$cardinalityTally = ['1:1' => 0, '1:N' => 0, '—' => 0];
$polymorphicTables = 0;

$rows = [];
foreach ($tables as $table) {
    $relation = relationFor($table, $foreignKeys, $primaryKey, $columns, $tableSet);
    $linkTally[$relation['link']]++;
    $allConstraintCount += count($foreignKeys[$table]);

    if (isset($mirrorSet[$table]) && $foreignKeys[$table] !== []) {
        $withForeignKey++;
        $constraintCount += count($foreignKeys[$table]);
    }
    if (isset($mirrorSet[$table]) && $foreignKeys[$table] === []) {
        $withoutForeignKey++;
    }

    if ($polymorphicBase[$table] !== '') {
        $polymorphicTables++;
    }

    $cardinalityTally[$relation['cardinality']]++;
    $rows[] = ['table' => $table] + $relation;
}

// Which of the five key entities have inbound constraints at all?
$inbound = [];
foreach (['tf_messages', 'tf_dialogs', 'tf_users', 'tf_chats', 'tf_channels'] as $entity) {
    $inbound[$entity] = 0;
}
foreach ($tables as $table) {
    foreach ($foreignKeys[$table] as $fk) {
        if (isset($inbound[$fk['table']])) {
            $inbound[$fk['table']]++;
        }
    }
}

// Tables declaring MORE THAN ONE constraint. `relationFor()` reports one relation per
// table, so it can only show the FIRST constraint of such a table; the set is measured
// here and published in "Honest limits" rather than left for a reader to discover.
$multiConstraint = array_values(array_filter(
    $tables,
    fn (string $t): bool => count($foreignKeys[$t]) > 1,
));

// -----------------------------------------------------------------------------
// 4b. THE GAP `soft 0` CANNOT SEE.
//
// WHY THIS BLOCK EXISTS — `soft 0` IS NOT "EVERY RELATION IS ENFORCED".
//
// `relationFor()` above returns ONE relation per table and takes the FIRST
// foreign key it finds. On this tree that first FK is the tenant FK to
// `telegram_accounts` on all 265 mirror tables, so every intra-mirror edge is
// never examined and the `soft` branch is unreachable — which is why it prints
// `soft 0`. Read naively that line says "no unenforced relation remains". It
// does not say that, and publishing it alone is the defect this block fixes: the
// document must not describe an integrity the schema does not have.
//
// The measurement is therefore NOT re-derived here. `MirrorRelationPlan` is the
// class the anti-rot gate (`EveryFkableRelationHasAConstraintTest`) builds its
// red list from, so wiring the generator to the SAME class on the SAME
// already-built connection is what makes the two documents agree by
// construction rather than by coincidence. `singleParent()` are the relations
// whose parent's key columns all exist on the child, i.e. mechanically FK-able
// today; the unenforced subset is those with no matching constraint.
//
// The `hasForeignKey()` predicate below is the gate's, including its
// order-insensitive comparison, because a stricter or looser predicate here
// would make the two counts disagree for a reason that is a predicate bug
// rather than a schema fact.
$relationPlan = MirrorRelationPlan::fromConnection(
    $capsule->getConnection(),
    MirrorCatalog::load(
        SchemaArtifacts::path('mirror-catalog.json'),
        TlParser::parseFile(SchemaArtifacts::source('api_full.tl')),
    ),
);

/**
 * Every FOREIGN KEY declared by `$table`, grouped — one entry per CONSTRAINT.
 *
 * `groupForeignKeys()` above already produced exactly this from `PRAGMA
 * foreign_key_list`; re-reading it through the driver rather than rebuilding a
 * second grouping keeps the two measurements on one source.
 *
 * @param  list<array{table: string, from: list<string>, to: list<string>, on_delete: string}>  $declared
 * @return list<array{table: string, from: list<string>, to: list<string>, on_delete: string}>
 */
function constraintsDeclaredBy(string $table, array $declared): array
{
    return array_values(array_filter(
        $declared,
        static fn (array $fk): bool => $fk['table'] !== $table,
    ));
}

/**
 * Does `$child` carry a FOREIGN KEY to `$parent` over exactly `$columns`?
 *
 * Order-INSENSITIVE on both sides: SQLite emits `PRAGMA foreign_key_list` rows
 * in REVERSE key order for a composite constraint, and a constraint that
 * matches its target in a different declaration order is the same constraint.
 * Mirrors the gate's own `hasForeignKey()`.
 *
 * @param  list<string>  $columns
 */
function hasForeignKey(string $child, array $columns, string $parent, array $foreignKeys): bool
{
    $from = $columns;
    sort($from);

    foreach (constraintsDeclaredBy($child, $foreignKeys[$child] ?? []) as $fk) {
        if ($fk['table'] !== $parent) {
            continue;
        }

        $legs = $fk['from'];
        sort($legs);

        if ($legs === $from) {
            return true;
        }
    }

    return false;
}

/**
 * Every mechanical reason a shipped migration recorded for NOT adding a
 * constraint, keyed by the child table it names.
 *
 * Read by `ReflectionClass::getConstant('REFUSED')` off the migration objects
 * this script already loaded, so the published reason is the string the
 * migration SHIPS. It is deliberately NOT re-derived: the refusal is an
 * engine-level fact about the target dialect (MySQL refuses a `bigint` child
 * against an `int` parent with ERROR 3780) that sqlite's dynamic type
 * affinity cannot see — measured, both legs execute to `INTEGER` here — so a
 * derivation from this connection could only ever produce a plausible guess.
 *
 * The consequence is stated rather than hidden: a relation with no `REFUSED`
 * entry is published with NO reason and is a finding, not a formatting gap.
 *
 * @param  array<string, object>  $migrationClasses
 * @return array<string, array{reason: string, migration: string}>
 */
function refusedReasons(array $migrationClasses): array
{
    $reasons = [];

    foreach ($migrationClasses as $name => $migration) {
        $constant = (new ReflectionClass($migration))->getConstant('REFUSED');

        if (! is_array($constant)) {
            continue;
        }

        foreach ($constant as $table => $reason) {
            $reasons[(string) $table] = [
                'reason' => (string) $reason,
                'migration' => $name,
            ];
        }
    }

    ksort($reasons);

    return $reasons;
}

/**
 * The first SENTENCE of a migration's `REFUSED` prose, for a table cell.
 *
 * Split at a period followed by whitespace, not at the first period: the reasons
 * are dense with dotted identifiers (`tf_dialog_filters.id`, `bigInteger`), and
 * a naive split there truncates the cell at "…but tf_dialog_filters." — cutting
 * off the very mechanism (the `integer` / `bigInteger` mismatch) the cell exists
 * to name. Measured on the tree at HEAD: a delimiter-only split loses the reason
 * on every one of the dialog-filter rows.
 *
 * Returned whole when there is no sentence boundary, so a reason is never
 * silently truncated to nothing.
 */
function firstSentence(string $reason): string
{
    $boundary = strpos($reason, '. ');

    if ($boundary === false) {
        $lastStop = strrpos($reason, '.');

        return $lastStop === false ? $reason : substr($reason, 0, $lastStop + 1);
    }

    return substr($reason, 0, $boundary + 1);
}

/**
 * A table cell naming the mechanical refusal, plus where the full text lives.
 *
 * The `REFUSED` prose is written for a migration's docblock and runs to several
 * hundred characters — correct there, unreadable in a five-column table. So the
 * cell carries the mechanism in short form and the row points at the migration
 * that ships the full argument. The full text is reproduced in full further
 * down, from the same measured source, so nothing is lost by abbreviating the
 * cell: a reader who wants the whole reason gets all of it in this document.
 *
 * A `|` inside the cell would open a new markdown column, so it is escaped
 * rather than allowed to corrupt the row's shape.
 *
 * @param  array{child:string,parent:string,columns:list<string>,reason:string,migration:string|null}  $relation
 */
function refusalCell(array $relation): string
{
    if ($relation['reason'] === '') {
        return '**no reason recorded**';
    }

    return str_replace('|', '\|', firstSentence($relation['reason']))
        .' *(full reason below, from `'.$relation['migration'].'`)*';
}

$refused = refusedReasons($migrationClasses);

/** @var list<array{child:string,parent:string,columns:list<string>,reason:string,migration:string|null}> $unenforced */
$unenforced = [];

foreach ($relationPlan->singleParent() as $relation) {
    if (hasForeignKey($relation['child'], $relation['columns'], $relation['parent'], $foreignKeys)) {
        continue;
    }

    $record = $refused[$relation['child']] ?? null;

    $unenforced[] = [
        'child' => $relation['child'],
        'parent' => $relation['parent'],
        'columns' => $relation['columns'],
        'reason' => $record['reason'] ?? '',
        'migration' => $record['migration'] ?? null,
    ];
}

$unenforcedCount = count($unenforced);
$unenforcedWithReason = count(array_filter(
    $unenforced,
    static fn (array $relation): bool => $relation['reason'] !== '',
));
$unenforcedWithoutReason = $unenforcedCount - $unenforcedWithReason;

// -----------------------------------------------------------------------------
// 5. Emit.
// -----------------------------------------------------------------------------

/**
 * The commit this measurement describes: the last commit that touched the
 * MIGRATIONS, not HEAD.
 *
 * That distinction is load-bearing and it was wrong at first. `HEAD` is
 * unusable as a staleness stamp here: `tests/Schema/RelationGraphIsCurrentTest.php`
 * compares the committed document byte-for-byte against a fresh run, so a stamp
 * taken from HEAD moves on EVERY commit — including commits that touch no
 * migration at all. The gate would then go red the moment anyone else committed
 * anything, and a gate that fires on unrelated work gets disabled, which is how
 * `model-relation-gap.md` came to report a fixed defect as open.
 *
 * Stamping the migrations' own last commit is the honest provenance: the number
 * is stable exactly as long as the schema is, and it MOVES the instant a
 * migration changes, which is the moment the document is stale.
 *
 * So: `measured at commit <sha>` means "the shipped migrations as of <sha>",
 * not "the repository as of <sha>".
 */
$commit = trim((string) shell_exec(
    'git -C '.escapeshellarg($root).' log -1 --format=%h -- src/Laravel/Migrations/ 2>/dev/null'
));

if ($commit === '') {
    $commit = trim((string) shell_exec('git -C '.escapeshellarg($root).' rev-parse --short=8 HEAD 2>/dev/null'));
}

if ($commit === '') {
    $commit = 'unknown';
}

$coreFamilies = [
    'tf_messages' => 'message',
    'tf_dialogs' => 'dialog',
    'tf_users' => 'user',
    'tf_chats' => 'chat',
    'tf_channels' => 'channel',
];

/**
 * The entity itself plus every table whose parent is it, in graph order.
 *
 * @var array<string, list<array<string, mixed>>>
 */
$familyMembers = array_fill_keys(array_keys($coreFamilies), []);
foreach ($rows as $row) {
    if (isset($coreFamilies[$row['table']])) {
        $familyMembers[$row['table']][] = $row;
    }
    if ($row['parent'] !== null && isset($coreFamilies[$row['parent']])) {
        $familyMembers[$row['parent']][] = $row;
    }
}

$md = [];
$md[] = '<!-- GENERATED FILE. Do not hand-edit. Run: php bin/generate-relation-graph.php -->';
$md[] = '<!-- measured at commit '.$commit.' -->';
$md[] = '';
$md[] = '# Mirror relation graph — measured from the executed DDL';
$md[] = '';
$md[] = 'Generated by [`bin/generate-relation-graph.php`](../../bin/generate-relation-graph.php) at commit `'.$commit.'`.';
$md[] = 'Re-run it after any migration; `tests/Schema/RelationGraphIsCurrentTest.php` fails if this file stops matching the schema.';
$md[] = '';
$md[] = 'Companion documents: [`model-relation-gap.md`](model-relation-gap.md) (what can be JOINed and what silently';
$md[] = 'cannot — an analysis, partly stale, honest about its own limits), and';
$md[] = '[`mtproto-data-shape-map.md`](mtproto-data-shape-map.md) (method → shape → reference).';
$md[] = '';
$md[] = '## Method — and why it is a script';
$md[] = '';
$md[] = 'Every number below was produced by **executing** all '.count($migrationFiles).' shipped migration files in apply order against an in-memory sqlite';
$md[] = 'connection and reflecting with `PRAGMA table_info` / `PRAGMA foreign_key_list`.';
$md[] = 'Nothing here transcribes a table shape and nothing here parses PHP.';
$md[] = '';
$md[] = '**A text search cannot produce this document.** Foreign keys reach this schema through shared `Blueprint`';
$md[] = 'helpers — `linkChild()`, `participantLink()`, `channelUpdateLink()`, `differenceLink()`, `factKey()` — which are';
$md[] = 'private static methods on the migration classes. `grep "->foreign("` sees only the literal inline declarations';
$md[] = 'and misses every constraint a helper emits, and it cannot see primary keys or the composite column sets that';
$md[] = 'decide cardinality. The same blindness applies to counting: `PRAGMA foreign_key_list` emits **one row per column**';
$md[] = 'of a composite constraint, so counting rows counts columns. This generator groups by constraint `id`.';
$md[] = '';
$md[] = 'Scope: what the **migrations** declare. No live database was connected, no `.env` or credential file was read,';
$md[] = 'no Telegram call was made. A real schema may have drifted in either direction.';
$md[] = '';
$md[] = '## Headline counts';
$md[] = '';
$md[] = '| measure | count |';
$md[] = '|---|---|';
$md[] = '| migration files executed | '.count($migrationFiles).' |';
$md[] = '| tables created, all prefixes | '.count($tables).' |';
$md[] = '| `tf_*` mirror tables | '.count($mirror).' |';
$md[] = '| **`tf_*` tables with at least one FOREIGN KEY** | **'.$withForeignKey.'** |';
$md[] = '| **`tf_*` tables with no FOREIGN KEY at all** | **'.$withoutForeignKey.'** |';
$md[] = '| foreign-key constraints declared BY `tf_*` tables (grouped, not per-column) | '.$constraintCount.' |';
$md[] = '| foreign-key constraints declared by all '.count($tables).' tables | '.$allConstraintCount.' |';
$md[] = '| tables that are children of no other table (`root`) | '.$linkTally['root'].' |';
$md[] = '| relations by link kind | fk '.$linkTally['fk'].' · soft '.$linkTally['soft'].' · root '.$linkTally['root'].' |';
$md[] = '| **single-parent relations that are FK-able but carry NO constraint** | **'.$unenforcedCount.'** |';
$md[] = '| relations by cardinality | 1:1 '.$cardinalityTally['1:1'].' · 1:N '.$cardinalityTally['1:N'].' |';
$md[] = '| tables carrying a polymorphic `{base}_type` + `{base}_id` peer pair | '.$polymorphicTables.' |';
$md[] = '';
$md[] = '## What the three link kinds mean';
$md[] = '';
$md[] = '| link | meaning | joinable? | enforced by the database? |';
$md[] = '|---|---|---|---|';
$md[] = '| `fk` | a FOREIGN KEY constraint exists | yes | **yes** |';
$md[] = '| `soft` | child of a `tf_*` parent whose PK covers its own, no FK declared | yes | no — convention only |';
$md[] = '| `root` | no parent found | — | — |';
$md[] = '';
$md[] = 'A fourth property rides alongside the link, and is NOT a fourth kind: a table with a `{base}_type` +';
$md[] = '`{base}_id` sibling pair holds a **polymorphic** reference that resolves across `tf_users` / `tf_chats` /';
$md[] = '`tf_channels` on the `_type` int. It is joinable, and no single FOREIGN KEY can constrain it, because one';
$md[] = 'constraint cannot branch. Those two facts — polymorphic pair present, link kind `soft` or `root` — together';
$md[] = 'describe the mirror\'s largest integrity gap, and the graph says so per row rather than in prose.';
$md[] = '';
$md[] = 'A `soft` relation is **not** a defect on its own: it is the price of a composite key that the wire scopes per';
$md[] = 'peer. It is recorded here so that "the mirror must never allow orphan facts" is never read as true by';
$md[] = 'default. Enforce nothing, and nothing stops an orphan landing.';
$md[] = '';
$md[] = '## Known gaps — `soft '.$linkTally['soft'].'` does NOT mean "everything is enforced"';
$md[] = '';
$md[] = '**Read the `soft '.$linkTally['soft'].'` line above as a statement about this generator\'s classification, not about the';
$md[] = 'schema\'s integrity. There are currently '.$unenforcedCount.' single-parent relations that are mechanically FK-able';
$md[] = 'and carry NO constraint.** They are listed in full below, each with the mechanical reason it is not';
$md[] = 'constrained.';
$md[] = '';
$md[] = '### WHY THE TWO FIGURES DIFFER — `relationFor()` TAKES THE FIRST FK IT FINDS';
$md[] = '';
$md[] = 'This generator derives **one relation per table** (`relationFor()`), and on a table carrying several';
$md[] = 'constraints it can only report the **first** one. On this tree that first constraint is the tenant FK';
$md[] = 'to `telegram_accounts`, which is present on all '.$withForeignKey.' mirror tables — so those tables classify as';
$md[] = '`fk` against `telegram_accounts` and their intra-mirror edge is **never examined**. A table with that';
$md[] = 'tenant FK can therefore never reach the `soft` branch at all:';
$md[] = '';
$md[] = '> `soft` = child of a `{prefix}_…` parent whose primary key covers its own, **and no FK declared at all**.';
$md[] = '';
$md[] = 'That last clause is what collapses the figure. `soft '.$linkTally['soft'].'` means "no table reached that branch, because every';
$md[] = 'table has an FK somewhere else", NOT "every declared parent link is enforced". The two numbers count';
$md[] = 'different populations, so they neither agree nor contradict: `soft` is over '.(count($tables)).' executed tables and';
$md[] = 'reports the first FK each one has; the gap figure below is over `MirrorRelationPlan`\'s derived intra-mirror';
$md[] = 'edges and reports the ones with no constraint. **A green suite must not be read as "the mirror has no';
$md[] = 'unconstrained relation" while this section is non-empty.**';
$md[] = '';
$md[] = '### THE GAP COUNT IS MEASURED, NOT TRANSCRIBED';
$md[] = '';
$md[] = 'It comes from `MirrorRelationPlan::singleParent()` — the same class, run against the same in-memory sqlite';
$md[] = 'connection this script already built above, in the same migration apply order — minus the relations that DO';
$md[] = 'carry a matching FOREIGN KEY. `singleParent()` is the population whose parent key columns all exist on the';
$md[] = 'child, i.e. mechanically FK-able today; the subtraction uses the same order-insensitive constraint';
$md[] = 'predicate as `tests/Schema/Mirror/EveryFkableRelationHasAConstraintTest`. That gate builds its red list from';
$md[] = 'this same class, so **the figure below and the gate\'s figure are the same measurement, not two that happen';
$md[] = 'to agree** — if they ever disagree, one of them has a bug, which is the useful failure.';
$md[] = '';
$md[] = '| measure | count |';
$md[] = '|---|---:|';
$md[] = '| intra-mirror edges derived from the executed DDL (`singleParent` + `polymorphic` + `unconstrained`) | '.(array_sum($relationPlan->counts())).' |';
foreach ($relationPlan->counts() as $population => $count) {
    $md[] = '| &nbsp;&nbsp;of which `'.$population.'` | '.$count.' |';
}
$md[] = '| **single-parent relations mechanically FK-able and carrying NO constraint** | **'.$unenforcedCount.'** |';
$md[] = '| &nbsp;&nbsp;naming a mechanical reason | '.$unenforcedWithReason.' |';
$md[] = '| &nbsp;&nbsp;with **no** recorded reason — a finding, not a formatting gap | '.$unenforcedWithoutReason.' |';
$md[] = '';
if ($unenforced === []) {
    $md[] = 'None. Every `singleParent` relation carries a real constraint.';
} else {
    $md[] = '### THE '.$unenforcedCount.' UNCONSTRAINED RELATIONS';
    $md[] = '';
    $md[] = '| # | child | legs | parent | why no constraint |';
    $md[] = '|---|---|---|---|---|';
    foreach ($unenforced as $index => $relation) {
        $md[] = '| '.($index + 1)
            .' | `'.$relation['child'].'`'
            .' | `'.implode('`, `', $relation['columns']).'`'
            .' | `'.$relation['parent'].'`'
            .' | '.refusalCell($relation)
            .' |';
    }
    $md[] = '';
    if ($unenforcedWithoutReason > 0) {
        $md[] = '**'.$unenforcedWithoutReason.' of these carry no recorded reason.** An excuse must name the mechanism that';
        $md[] = 'excuses it; an empty string is not an excuse. That is a finding in its own right: either the';
        $md[] = 'migration that owns the family needs its `REFUSED` entry, or the constraint genuinely has no';
        $md[] = 'mechanical excuse and should be landed.';
        $md[] = '';
    }
    $md[] = 'The reason column is read by `ReflectionClass::getConstant(\'REFUSED\')` off the migration that';
    $md[] = '**ships** it, so it is the string the migration executes rather than a copy kept here. It is';
    $md[] = 'deliberately not re-derived here: these refusals are facts about the **target dialect** that sqlite\'s';
    $md[] = 'dynamic type affinity cannot express — measured, both legs of the mismatch below execute to `INTEGER`';
    $md[] = 'on this connection — so a derivation from this engine could only ever produce a plausible guess.';
    $md[] = 'A green sqlite suite must not be the thing that decides a MySQL refusal is acceptable.';
    $md[] = '';
    $md[] = '**Decision of record: [`D-020`](../../docs/decisions.md) in `docs/decisions.md`** — the `tf_dialog_filters`';
    $md[] = 'depth-1 refusals stay UNCONDITIONAL, and a migration must not produce a per-driver schema. It records';
    $md[] = 'the alternatives (narrow the child, widen the parent, driver-gate the refusal) and why each loses,';
    $md[] = 'including what would make it wrong. The gate at';
    $md[] = '`tests/Schema/Mirror/EveryFkableRelationHasAConstraintTest.php` is **expected to stay red on exactly';
    $md[] = 'these '.$unenforcedCount.' relations** until the parent key is widened by a migration or that decision is';
    $md[] = 'revisited. That redness is load-bearing, not a shortfall.';
    $md[] = '';
    $md[] = '### THE FULL REASON, PER RELATION';
    $md[] = '';
    $md[] = 'Quoted in full from the `REFUSED` constant each migration ships, read by reflection at generation time.';
    $md[] = 'A sibling family can be refused for a DIFFERENT reason — `2026_10_04_000800` declines one relation because';
    $md[] = 'the derived leg set is wrong, where these are refused because the ENGINE rejects a correct leg set — so';
    $md[] = 'the reasons are reproduced rather than summarised into one shared sentence.';
    $md[] = '';
    foreach ($unenforced as $relation) {
        if ($relation['reason'] === '') {
            $md[] = '**`'.$relation['child'].'` → `'.$relation['parent'].'` — no reason recorded.** No migration ships a';
            $md[] = '`REFUSED` entry naming it, so nothing explains the absence of the constraint. That is the';
            $md[] = 'finding: land the constraint, or record the mechanical reason.';
            $md[] = '';

            continue;
        }

        $md[] = '- **`'.$relation['child'].'` → `'.$relation['parent'].'`** *(shipped by `'.$relation['migration'].'`)*';
        $md[] = '';
        $md[] = '  > '.str_replace('|', '\|', $relation['reason']);
        $md[] = '';
    }
}
$md[] = '';
$md[] = '## Inbound constraints on the five key entities';
$md[] = '';
$md[] = '| entity | constraints pointing AT it |';
$md[] = '|---|---|';
foreach ($inbound as $entity => $count) {
    $md[] = '| `'.$entity.'` | '.$count.($count === 0 ? ' — **none**' : '').' |';
}
$md[] = '';
$md[] = '## Core families';
$md[] = '';
$md[] = 'One `erDiagram` per core family. Edge labels state the cardinality and the link kind, because a bare';
$md[] = 'mermaid edge hides exactly the distinction this document exists to record.';
$md[] = '';

foreach ($coreFamilies as $entity => $label) {
    $members = $familyMembers[$entity];
    $children = array_values(array_filter($members, fn (array $m): bool => $m['table'] !== $entity));

    $md[] = '### `'.$entity.'` ('.$label.' — '.$entity.' plus '.count($children).' measured child table'.(count($children) === 1 ? '' : 's').')';
    $md[] = '';
    $md[] = '```mermaid';
    $md[] = 'erDiagram';
    $md[] = '    '.$entity.' {';
    $md[] = '        bigint account_id "PK"';
    foreach ($primaryKey[$entity] as $keyColumn) {
        if ($keyColumn === 'account_id') {
            continue;
        }
        $md[] = '        bigint '.$keyColumn.' "PK"';
    }
    $md[] = '    }';

    foreach ($children as $child) {
        $md[] = '    '.$child['table'].' {';
        $md[] = '        bigint account_id "PK"';
        $md[] = '    }';
    }

    foreach ($children as $child) {
        $crow = $child['cardinality'] === '1:1' ? '||--||' : '||--o{';
        $md[] = '    '.$entity.' '.$crow.' '.$child['table'].' : "'.$child['cardinality'].' '.$child['link'].'"';
    }

    $md[] = '```';
    $md[] = '';
    $md[] = 'Edge labels: the cardinality, then the link kind. `fk` = a FOREIGN KEY constraint exists and the';
    $md[] = 'database enforces it. `soft` = this parent\'s PK covers the child\'s and no constraint declares the link,';
    $md[] = 'so it is joinable and unenforced. A bare diagram would hide exactly that difference, which is the';
    $md[] = 'difference this document exists to record.';
    $md[] = '';
}

// The full per-table graph.
$md[] = '## The full graph, one row per table';
$md[] = '';
$md[] = '`join` is the child-side column set: for `fk` rows the constraint\'s own columns, otherwise the child';
$md[] = 'primary key. `polymorphic` marks a table that ALSO holds a `{base}_type` + `{base}_id` peer pair — it is an';
$md[] = 'attribute of the row, not a different kind of parent link.';
$md[] = '';
$md[] = '<!-- BEGIN GENERATED TABLE GRAPH -->';
$md[] = '';
$md[] = '| # | table | parent | join | cardinality | link | polymorphic | pk cols |';
$md[] = '|---|---|---|---|---|---|---|---|';
foreach ($rows as $index => $row) {
    $md[] = '| '.($index + 1)
        .' | `'.$row['table'].'`'
        .' | '.($row['parent'] === null ? '—' : '`'.$row['parent'].'`')
        .' | '.($row['join'] === [] ? '—' : '`'.implode('`, `', $row['join']).'`')
        .' | '.$row['cardinality']
        .' | `'.$row['link'].'`'
        .' | '.($polymorphicBase[$row['table']] === ''
            ? 'no'
            : 'yes — `'.$polymorphicBase[$row['table']].'_type` / `'.$polymorphicBase[$row['table']].'_id`')
        .' | '.($primaryKey[$row['table']] === [] ? '**none**' : '`'.implode('`, `', $primaryKey[$row['table']]).'`')
        .' |';
}
$md[] = '';
$md[] = '<!-- END GENERATED TABLE GRAPH -->';
$md[] = '';
$md[] = '## Honest limits';
$md[] = '';
$md[] = '- **Migrations only.** No live database was connected. Every claim is about what the shipped DDL declares.';
$md[] = '- **Inheritance is inferred, not declared.** A `soft` parent is derived from a table-name prefix whose primary key';
$md[] = '  covers the child\'s. That is a strong convention in this schema, not a constraint the database checks. Where';
$md[] = '  the convention and the DDL disagree, the DDL won — a table with an FK is always reported against that FK.';
$md[] = '- **Polymorphic pairs are detected by name.** A `{base}_type` + `{base}_id` sibling pair is recorded as a';
$md[] = '  polymorphic reference. Whether the `_type` int maps to `tf_users` / `tf_chats` / `tf_channels` where this';
$md[] = '  document implies is a reading of the wire, not a measurement of the schema, and is not proven here.';
$md[] = '- **`cardinality` is derived from key shape, not from row counts.** `1:1` means the child\'s primary key equals';
$md[] = '  its parent\'s; `1:N` means it is a strict superset. A `1:N` claim is a statement about the key, and says';
$md[] = '  nothing about how many rows actually exist.';
$md[] = '- **This graph does NOT close the analysis gap in `model-relation-gap.md`.** It makes the unanalysed set VISIBLE';
$md[] = '  — every `root` and every `soft` row is a relation no constraint backs — but a measured table list is not a';
$md[] = '  read of each table\'s semantics. `model-relation-gap.md` concedes roughly 80 tables were never analysed, and';
$md[] = '  that concession still stands after this document was generated.';
$md[] = '- **A table declaring more than one FOREIGN KEY shows only the FIRST constraint in its row.** Measured: '.count($multiConstraint).' of '.count($tables).' tables'
    .($multiConstraint === [] ? '.' : ': '.implode(', ', array_map(fn (string $t): string => '`'.$t.'`', $multiConstraint)).'.');
$md[] = '  The `foreign-key constraints declared` rows and the inbound-constraint table count EVERY one of them, so their';
$md[] = '  totals exceed the `fk` link-kind tally — which counts one ROW per table — by exactly this many. The second';
$md[] = '  constraint of such a table lives in those per-constraint counts, never in a second row.';
$md[] = '- **No row-level data.** No row counts, so the practical cost of a `soft` orphan window is unquantified.';
$md[] = '- **`soft '.$linkTally['soft'].'` IS A CLASSIFICATION ARTEFACT, NOT AN INTEGRITY MEASURE.** `relationFor()` returns ONE';
$md[] = '  relation per table and takes the FIRST foreign key it finds, which on this tree is the tenant FK to';
$md[] = '  `telegram_accounts` on all '.$withForeignKey.' mirror tables. A table carrying that FK can never reach the `soft`';
$md[] = '  branch, so `soft '.$linkTally['soft'].'` reports how many tables have NO foreign key AT ALL and must not be read as a';
$md[] = '  count of unenforced relations. **The honest measure of unenforced relations is the "Known gaps" figure';
$md[] = '  above: '.$unenforcedCount.'.** Both are measured; they count different populations, so they do not contradict';
$md[] = '  each other — and neither of them may be quoted as "the mirror enforces its relations".';
$md[] = '- **THE '.$unenforcedCount.' UNCONSTRAINED RELATIONS ARE REFUSALS, NOT `soft` LINKS, AND WERE NOT RELABELLED.** A';
$md[] = '  `soft` link is joinable-by-convention; these are joinable AND mechanically FK-able, and carry no constraint';
$md[] = '  because a migration declined to add one and recorded why. Calling them `soft` would have been the easy way to';
$md[] = '  make `soft 0` consistent, and it would have been the wrong one: it would have hidden that a mechanical';
$md[] = '  obligation exists and is unmet, behind a category whose whole meaning is "we never wrote this down".';
$md[] = '  Their mechanical reasons are facts about the TARGET dialect (MySQL ERROR 3780) that this sqlite connection';
$md[] = '  cannot reproduce — measured, the mismatched legs execute to the same declared type here — so they are read';
$md[] = '  from the migrations that ship them, by reflection, and reproduced in full above.';
$md[] = '- **THE PEER-SCOPED KEY WIDENING IS `Schema::create`-ONLY. IT DOES NOT ALTER AN EXISTING TABLE.**';
$md[] = '  The rows above show the primary keys as the shipped DDL DECLARES them, which is what a';
$md[] = '  freshly-created database gets. NO ALTER adds the peer columns to `tf_messages` — the dial';
$md[] = '  does carry 19 other `Schema::table(...)` sites (8 foreign-key additions in';
$md[] = '  `2026_09_14_299999`, plus ALTERs on `tf_store_buckets`, `telegram_accounts` and';
$md[] = '  `tl_user_bindings`), but none of them touch `tf_messages`. There is also no backfill';
$md[] = '  migration and no automated rebuild step.';
$md[] = '  On a database the dial was applied to BEFORE the widening, `tf_messages` keeps its old';
$md[] = '  `(account_id, id)` primary key AND has no `peer_type`/`peer_id` columns at all, so the';
$md[] = '  graph rows for it are aspirational there, not descriptive.';
$md[] = '  **WHAT THE OPERATOR ACTUALLY SEES — MEASURED 2026-10-04 (a previous version of this';
$md[] = '  section wrongly said the 80.98% silent-overwrite rate simply "continues, unchanged"):**';
$md[] = '  the mirror does NOT keep overwriting silently. It STOPS WRITING, LOUDLY.';
$md[] = '  `UpdateIngestor:409`\'s `updateOrInsert` on `[\'account_id\',\'peer_type\',\'peer_id\',';
$md[] = '  \'id\']` raises Postgres `SQLSTATE 42703 … column "peer_type" does not exist` on the';
$md[] = '  existence probe, before any write lands and with the pre-existing row left intact;';
$md[] = '  `SafeDelete` (`:194-220`) and the search index fail the same way.';
$md[] = '  An in-place widen is not merely omitted, it is IMPOSSIBLE as declared: `peer_type` and';
$md[] = '  `peer_id` are `NOT NULL` with no default, so an ALTER adding them to a populated table has';
$md[] = '  no legal value for the rows already there. A full-history RE-BACKFILL is therefore still';
$md[] = '  REQUIRED — drop the mirror tables and re-ingest from the wire.';
$md[] = '  And the rows that already collided under `(account_id, id)` are **UNRECOVERABLE**: the';
$md[] = '  mirror upserts, so the overwrite already happened and nothing in the database records';
$md[] = '  which rows it destroyed. The rebuild is required either way; only the failure MODE was';
$md[] = '  wrong above, and it was wrong in the direction that would send an operator hunting';
$md[] = '  silent corruption instead of diagnosing a stopped mirror.';

$output = implode("\n", $md)."\n";
$target = $root.'/docs/schema/mirror-relation-graph.md';

$check = in_array('--check', $argv, true);

if ($check) {
    $existing = is_file($target) ? (string) file_get_contents($target) : '';

    if ($existing !== $output) {
        fwrite(STDERR, "STALE: docs/schema/mirror-relation-graph.md does not match the executed schema.\n");
        fwrite(STDERR, "Run: php bin/generate-relation-graph.php\n");

        exit(1);
    }

    fwrite(STDOUT, "fresh at commit {$commit}\n");

    exit(0);
}

if (! is_dir(dirname($target))) {
    fwrite(STDERR, 'no such directory: '.dirname($target)."\n");

    exit(2);
}

file_put_contents($target, $output);

fwrite(STDOUT, sprintf(
    "wrote docs/schema/mirror-relation-graph.md at commit %s\n  tables %d (tf_ %d) · with FK %d · without FK %d · constraints %d\n",
    $commit,
    count($tables),
    count($mirror),
    $withForeignKey,
    $withoutForeignKey,
    $constraintCount
));
