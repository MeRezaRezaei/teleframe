<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorShape;
use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;

/**
 * Emits the Eloquent model layer for the mirror, and for every foreign key
 * the shipped DDL declares.
 *
 * ## What this emits, and why the shape is an argument
 *
 * Given a {@see MirrorShape} — the shipped migrations EXECUTED and the result
 * REFLECTED — this emits one model per mirror table, each carrying its real
 * column set, casts derived from the engine's own column type, and one
 * relation per declared foreign key, on both sides of the constraint.
 *
 * Given no shape, it falls back to the scheme-only projection the synthetic
 * unit tests exercise. That fallback exists so those tests keep measuring
 * what they were written to measure; it is not the production path.
 *
 * ## Relations come from declarations, never from a name
 *
 * A relation is emitted only for a constraint the engine DECLARES. This is
 * not stylistic caution. Inferring `tf_messages_service` -> `tf_messages`
 * from the shared name prefix produced a false foreign key between two tables
 * Telegram stores as SIBLINGS; it silently discarded every `messageService`
 * update and rolled back 18 rows each time. A name prefix is a heuristic and
 * the declared constraint is the authority, so `foreignKeys()` is the only
 * input relations are built from — and a table with no declared constraint
 * gets no relation, because inventing one would fabricate a relationship
 * Telegram does not have.
 *
 * ## …AND WHERE A NAME *IS* READ — THE HORIZONTAL HALF
 *
 * The rule above governs the VERTICAL relations, which were the whole of what
 * this class emitted until 2026-10-13. The HORIZONTAL half —
 * {@see HorizontalEntityRelations} — is where a NAME is read, and the difference
 * matters:
 *
 *  - the `{base}_type` / `{base}_id` pairing is not a table-name prefix
 *    heuristic. It is the SHAPE the shipped migrations declare for every peer,
 *    and the discriminator's ENGINE-REPORTED TYPE is checked through the same
 *    `castFor()` the generated casts come from before anything is emitted — which
 *    is what keeps `venue` (a `GeoPoint` in TEXT columns) out;
 *  - a bare entity column (`user_id`, `chat_id`, `channel_id`) is never accepted
 *    on its name. `MirrorCatalog` must DECLARE the field on that table, which is
 *    what separates `tf_updates.user_id` (a `long` the `Update` union carries)
 *    from `tf_messages_media.user_id` — a "scalar residue of each ctor", nested
 *    objects deferred, holding 0 on essentially every row;
 *  - a column the engine ALREADY constrains is skipped, because the declared-FK
 *    pass emitted a relation for it on both sides and two accessors for one edge
 *    is how a host ends up reading the unconstrained one.
 *
 * The failure this guards is specific and was named by the owner: a `belongsTo`
 * on a column that holds 0 returns an EMPTY relation, and an empty relation is
 * indistinguishable from "genuinely no rows". Confidently wrong is worse than
 * absent. `tests/Schema/Mirror/HorizontalEntityRelationsTest` is the gate.
 *
 * ## Why composite constraints produce a two-sided shape
 *
 * Every in-layer constraint on this dial is COMPOSITE — measured, there is not
 * one single-column in-layer constraint — because the mirror is tenant-scoped
 * and `account_id` opens nearly all of them. That has one consequence worth
 * stating: `belongsTo()` accepts a single column pair, so it cannot express a
 * composite key. The two sides therefore carry different fidelity, honestly:
 *
 *  - the INVERSE side (`hasMany`/`hasOne`) passes the FULL composite column
 *    arrays, which Eloquent does support, so it reproduces the constraint
 *    exactly;
 *  - the FORWARD side (`belongsTo`) uses the trailing non-tenant leg, which is
 *    total WITHIN the account. The dropped `account_id` leg is not lost
 *    safety: {@see AccountScope} already filters every generated model to one
 *    account, so the pair is unique in the scope it is used in.
 *
 * ## Measured provenance
 *
 * Every number below is measured from the shipped DDL, by
 * `bin/generate-mirror-shape.php`, and re-measured by
 * `tests/Schema/ModelGeneratorTest.php` against a live execution of the
 * shipped migrations. Nothing here is transcribed.
 *
 *  - 265 mirror tables, 2043 mirror columns (SQLite reflection).
 *  - 475 foreign-key constraints declared BY `tf_*` tables. Of those, 211 have
 *    both endpoints inside the mirror and are emitted here as 422 relations.
 *    The remaining 264 are `account_id -> telegram_accounts.user_id`, whose
 *    parent is an application-layer table that no generated model covers; see
 *    `foreignKeys()` and the lane report for why they are not fabricated.
 *  - SQLite sees 6 distinct declared types across those columns where MySQL
 *    8.4 sees 17, because SQLite's affinity collapses `integer()`,
 *    `bigInteger()` and `tinyInteger()` alike and drops width and signedness
 *    entirely. Casts are therefore derived from the MySQL-measured artifact
 *    (`mirror-shape.json`); the mapping below accepts either vocabulary so
 *    the SQLite-backed tests exercise the same code path.
 *
 * ## The unsigned-bigint caveat, stated rather than hidden
 *
 * `bigint unsigned` casts to `int`, matching the house precedent
 * (`Laravel\Models\TfAccount::@property int $id`). PHP integers are signed
 * 64-bit, so a `bigint unsigned` above `PHP_INT_MAX` would not survive the
 * cast. No column currently holds such a value — Telegram ids and account ids
 * are far below that bound — but the cast is a real limit and is recorded
 * here rather than discovered later.
 */
final class ModelGenerator
{
    private const NS = 'MeRezaRezaei\Teleframe\Schema\Generated\Models';

    /**
     * The reflected shape of the shipped DDL, or null for the scheme-only
     * projection (the synthetic-scheme path the unit tests exercise).
     */
    private readonly ?MirrorShape $shape;

    /**
     * The committed field-level catalog, or null when it could not be read.
     *
     * Only mechanism 2 of {@see HorizontalEntityRelations} reads it; the
     * declared-foreign-key relations below do not, and never did.
     */
    private readonly ?MirrorCatalog $catalog;

    /**
     * Domains that have PeerResolution trait (types with peer_id/from_id columns).
     *
     * @var array<string, true>
     */
    private const PEER_RESOLUTION_DOMAINS = [
        'messages' => true,
        'dialogs' => true,
        'updates' => true,
        'stories' => true,
        'channel_participants' => true,
    ];

    public function __construct(?MirrorShape $shape = null, ?MirrorCatalog $catalog = null)
    {
        $this->shape = $shape;
        $this->catalog = $catalog ?? self::defaultCatalog();
    }

    /**
     * The committed catalog, loaded the one way it is loaded everywhere else.
     *
     * Read from the committed artifact rather than passed in, so the two entry
     * points — `bin/generate-mirror-shape.php` and `artisan teleframe:regenerate` —
     * cannot hand this generator different views of which field belongs to which
     * table. Null when the artifacts are unreadable, which leaves mechanism 2
     * silent and every other mechanism intact.
     */
    private static function defaultCatalog(): ?MirrorCatalog
    {
        try {
            return MirrorCatalog::load(
                SchemaArtifacts::path('mirror-catalog.json'),
                TlParser::parseFile(SchemaArtifacts::source('api_full.tl')),
            );
        } catch (\Throwable) {
            return null;
        }
    }

    public static function modelFqcn(string $class): string
    {
        return self::NS.'\\'.$class;
    }

    /**
     * MySQL `COLUMN_TYPE`, or a SQLite affinity class, to an Eloquent cast.
     *
     * Both vocabularies are accepted so the SQLite-backed tests exercise the
     * same mapping the MySQL-measured artifact uses. `COLUMN_TYPE` is read,
     * never `DATA_TYPE`: `DATA_TYPE` drops width and signedness, which is the
     * whole of what distinguishes `tinyint(1)` from `tinyint` here.
     */
    public static function castFor(string $columnType): ?string
    {
        // Normalise: lowercase, and drop the width/unsigned decoration so
        // `bigint unsigned` and `bigint` land on the same family.
        $type = strtolower(trim($columnType));

        // `tinyint(1)` is the one width that CARRIES MEANING: MySQL and
        // SQLite both spell the boolean-affinity column exactly this way, and
        // the mirror declares 450 of them. It must be matched before the
        // general `tinyint` rule below, or every flag silently reads as an
        // integer and `deleted`/`bot`/`verified` stop being truthy.
        if ($type === 'tinyint(1)') {
            return 'boolean';
        }

        $family = trim(str_replace(
            [' unsigned', ' signed'],
            '',
            explode('(', $type, 2)[0],
        ));

        return match ($family) {
            'tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint' => 'int',
            'float', 'double', 'real', 'decimal', 'numeric' => 'float',
            'text', 'tinytext', 'mediumtext', 'longtext',
            'varchar', 'char', 'string' => 'string',
            // SQLite reports a TIMESTAMP declaration as `datetime`, which is
            // the one vocabulary it keeps verbatim.
            'datetime', 'timestamp' => 'datetime',
            'date' => 'date',
            'json' => 'array',
            'blob' => 'string',
            default => null,
        };
    }

    /** @return array<string,string> class file path (Generated/Models/X.php) => content */
    public function generate(TlScheme $scheme): array
    {
        return $this->shape === null
            ? $this->generateFromScheme($scheme)
            : $this->generateFromShape();
    }

    /**
     * Generate the mirror model layer from a reflected shape, with no TL
     * scheme involved.
     *
     * Separate from {@see generate()} so the production entry point needs
     * nothing but the measurement: which tables are ANCHORS is read off
     * {@see Naming}'s own domain map, the same map `domainModel()` reads, so
     * the anchor set cannot drift from the naming rules. Reading a scheme
     * here would mean re-implementing the regenerator's file discovery and
     * merge order — a second copy of the parse, which is the drift this
     * repository keeps paying for.
     *
     * @return array<string,string>
     */
    public function generateFromMirrorShape(?MirrorShape $shape = null): array
    {
        $generator = $shape === null ? $this : new self($shape);

        return $generator->generateFromShape();
    }

    /**
     * The scheme-only projection: one model per TL domain.
     *
     * This is what the synthetic-scheme unit tests measure. It deliberately
     * emits no columns and no relations, because a synthetic scheme carries
     * neither — a test asserting a column here would be asserting against a
     * fixture rather than against the schema.
     *
     * @return array<string,string>
     */
    private function generateFromScheme(TlScheme $scheme): array
    {
        $files = [];
        $classes = [];

        // Collect which TL types map to each domain
        $domainTypes = [];
        $types = $scheme->types();
        ksort($types);
        foreach ($types as $type) {
            if ($type->name === 'Vector t' || $type->constructors() === []) {
                continue;
            }
            $domainTypesForType = [];
            foreach ($type->constructors() as $ctor) {
                $classification = Naming::classifyConstructor($ctor->name, $type->name);
                if ($classification === null) {
                    continue; // ephemeral — no model
                }
                $domainTypesForType[$classification['domain']] = true;
            }
            if ($domainTypesForType === []) {
                continue; // fully ephemeral type — no model
            }
            foreach (array_keys($domainTypesForType) as $domain) {
                $domainTypes[$domain][] = $type->name;
            }
        }

        // Emit one model per domain
        ksort($domainTypes);
        foreach ($domainTypes as $domain => $typeNames) {
            $class = Naming::domainModel($domain);
            $table = Naming::domainTable($domain);
            $classes[] = $class;

            $uses = ['AccountScoped'];

            if (isset(self::PEER_RESOLUTION_DOMAINS[$domain])) {
                $uses[] = 'PeerResolution';
            }

            // Collect constructor_id values for type-aware helpers — only
            // ctors that actually route to this domain (channel ctors live
            // under the Chat TL type but belong to the channels domain).
            $ctorIds = [];
            foreach ($typeNames as $typeName) {
                $type = $types[$typeName];
                foreach ($type->constructors() as $ctor) {
                    $classification = Naming::classifyConstructor($ctor->name, $typeName);
                    if (($classification['domain'] ?? null) !== $domain) {
                        continue;
                    }
                    $ctorIds[$ctor->name] = sprintf('%08x', $ctor->id);
                }
            }

            $body = [
                '/** Domain model for '.$domain.' (TL types: '.implode(', ', $typeNames).'). */',
                'final class '.$class.' extends TlAnchorModel',
                '{',
                '    use '.implode(', ', $uses).';',
                '',
                "    protected \$table = '{$table}';",
                '',
                '    protected $guarded = [];',
                '}',
            ];

            $imports = [
                'MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel',
                'MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped',
            ];
            if (isset(self::PEER_RESOLUTION_DOMAINS[$domain])) {
                $imports[] = 'MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution';
            }

            $files[$class.'.php'] = CodeWriter::phpFile(self::NS, [...self::useLines($imports), ...$body]);
        }

        Naming::assertUnique($classes, 'model class');
        ksort($files);

        return $files;
    }

    /**
     * The production path: one model per reflected mirror table, with its
     * columns, engine-derived casts, and one relation per DECLARED foreign
     * key on both sides of the constraint.
     *
     * @return array<string,string>
     */
    private function generateFromShape(): array
    {
        $shape = $this->shape;
        if ($shape === null) {
            throw new TlRegenerateException('generateFromShape called without a shape.');
        }

        $anchorClasses = $this->anchorClasses();

        // table => model class. The mapping is a BIJECTION onto table names,
        // because a non-anchor class name is the table's own stem in
        // StudlyCase. That is deliberate: it makes uniqueness structural
        // rather than accidental, so no two tables can collide, and no
        // generated class can accidentally shadow a hand-written model.
        $classFor = [];
        foreach ($shape->tableNames() as $table) {
            $classFor[$table] = $anchorClasses[$table] ?? self::tableModelClass($table);
        }
        Naming::assertUnique(array_values($classFor), 'mirror model class');

        $relations = $this->relationsFor($shape, $classFor);

        $files = [];
        foreach ($shape->tableNames() as $table) {
            $class = $classFor[$table];
            $files[$class.'.php'] = $this->renderModel($shape, $table, $class, $relations[$table] ?? []);
        }

        ksort($files);

        return $files;
    }

    /**
     * Model class for a reflected table.
     *
     * The stem is kept PLURAL rather than singularised. Singularising invites
     * exactly the mangling `Naming::DOMAIN_MODEL_NAMES` documents — `stories`
     * and `sticker_sets` do not survive a naive strip — and worse, it can
     * collide two distinct tables onto one class name. Keeping the stem
     * verbatim makes the table -> class map invertible, so uniqueness is a
     * property of the naming rule instead of a coincidence.
     */
    public static function tableModelClass(string $table): string
    {
        return 'Tf'.self::studlyStem($table);
    }

    public static function studlyStem(string $table): string
    {
        $stem = str_starts_with($table, 'tf_') ? substr($table, 3) : $table;

        return implode('', array_map(
            static fn (string $part): string => ucfirst($part),
            explode('_', $stem),
        ));
    }

    /**
     * Tables that are TL domain ANCHORS keep the historical `Tl*` class names,
     * so a long-standing model class does not move out from under every caller
     * that already names it.
     *
     * Read from {@see Naming}'s own domain map — the same map `domainModel()`
     * and `domainTable()` read — rather than from a scheme walk or a list of
     * table names, so the anchor set cannot drift from the rules it mirrors.
     *
     * @return array<string,string> table => class
     */
    private function anchorClasses(): array
    {
        $anchors = [];
        foreach (Naming::domainModelNames() as $domain) {
            $anchors[Naming::domainTable($domain)] = Naming::domainModel($domain);
        }

        return $anchors;
    }

    /**
     * Every relation to emit, per table.
     *
     * Two per declared in-layer constraint: the inverse on the parent, the
     * forward on the child. A constraint whose parent is not a generated model
     * is SKIPPED, not pointed somewhere plausible — see the class docblock for
     * the `telegram_accounts` case.
     *
     * @param  array<string,string>  $classFor
     * @return array<string,list<array{method:string,signature:string,body:string,doc:string}>>
     */
    private function relationsFor(MirrorShape $shape, array $classFor): array
    {
        /** @var array<string,array<string,true>> $used method-name guard, per table */
        $used = [];
        $relations = [];

        foreach ($shape->tableNames() as $child) {
            foreach ($shape->foreignKeys($child) as $fk) {
                $parent = $fk['to_table'];
                if ($parent === $child || ! isset($classFor[$parent])) {
                    continue;
                }

                // Both sides are emitted from ONE pair, or neither is.
                //
                // Eloquent cannot carry a composite key here: `Model::hasMany()`
                // passes `$foreignKey` through `qualifyColumn()`, a string
                // operation, and `HasOneOrMany::addConstraints()` hands it
                // straight to `where()` — so an array throws before any query
                // runs. That was found by invoking every generated relation
                // against the executed DDL, not by reading the framework.
                //
                // So the constraint is projected onto the pair that IS total
                // within an account: the trailing non-tenant leg. The dropped
                // `account_id` leg is not lost safety — `AccountScope` filters
                // every generated model to a single account, and the database
                // still enforces the full composite constraint. The docblock
                // names the whole constraint so nothing is hidden.
                $pair = self::tenantPair($fk['from'], $fk['to']);
                if ($pair === null) {
                    // Nothing but the tenant leg: there is no within-account
                    // discriminator to key a relation on, so this constraint
                    // yields no relation rather than a misleading one.
                    continue;
                }
                [$childColumn, $parentColumn] = $pair;

                // --- inverse side: parent hasMany the child.
                $one = count($fk['from']) === 1
                    && self::isOneToOne($shape->primaryKey($child), $fk['from']);
                $relations[$parent][] = [
                    'method' => self::uniqueMethod($used, $parent, self::methodName($child), $fk['name']),
                    'signature' => $one ? 'HasOne' : 'HasMany',
                    'body' => sprintf(
                        "return \$this->%s(%s::class, '%s', '%s');",
                        $one ? 'hasOne' : 'hasMany',
                        $classFor[$child],
                        $childColumn,
                        $parentColumn,
                    ),
                    'doc' => sprintf(
                        'Declared foreign key (%s): `%s`(%s) -> `%s`(%s), ON DELETE %s.'
                        .' Keyed on `%s` = `%s`; the `%s` leg is enforced by AccountScope'
                        .' and the full constraint by the database.',
                        $one ? '1:1' : '1:N',
                        $child,
                        implode(', ', $fk['from']),
                        $parent,
                        implode(', ', $fk['to']),
                        $fk['on_delete'],
                        $childColumn,
                        $parentColumn,
                        $fk['from'][0],
                    ),
                ];

                // --- forward side: child belongsTo the parent, same pair.
                $relations[$child][] = [
                    'method' => self::uniqueMethod($used, $child, self::methodName($parent), $fk['name']),
                    'signature' => 'BelongsTo',
                    'body' => sprintf(
                        "return \$this->belongsTo(%s::class, '%s', '%s');",
                        $classFor[$parent],
                        $childColumn,
                        $parentColumn,
                    ),
                    'doc' => sprintf(
                        'Declared foreign key: `%s`(%s) -> `%s`(%s). Keyed on `%s` = `%s`;'
                        .' the `%s` leg is enforced by AccountScope and the full constraint'
                        .' by the database.',
                        $child,
                        implode(', ', $fk['from']),
                        $parent,
                        implode(', ', $fk['to']),
                        $childColumn,
                        $parentColumn,
                        $fk['from'][0],
                    ),
                ];
            }
        }

        foreach ($relations as $table => $list) {
            usort($list, static fn (array $a, array $b): int => strcmp($a['method'], $b['method']));
            $relations[$table] = $list;
        }

        // The HORIZONTAL half, derived from the same executed shape.
        //
        // Merged here rather than in `generateFromShape()` so both halves pass
        // through `uniqueMethod()`: a horizontal name and a declared-FK name can
        // collide on one table (`peerTfMessages` against an FK-derived
        // `tfMessages`), and a name that silently overwrote another would make
        // one of the two relations unreachable.
        foreach ((new HorizontalEntityRelations($shape, $this->catalog))->relationsFor($classFor) as $table => $list) {
            foreach ($list as $relation) {
                $relation['method'] = self::uniqueMethod(
                    $used,
                    $table,
                    $relation['method'],
                    // No constraint name to disambiguate with — these edges have
                    // none by definition — so the edge itself is the tiebreak.
                    'horizontal:'.$table.'.'.$relation['method'],
                );
                $relations[$table][] = $relation + ['nullable' => false, 'usesPeerTrait' => false];
            }
        }

        foreach ($relations as $table => $list) {
            usort($list, static fn (array $a, array $b): int => strcmp($a['method'], $b['method']));
            $relations[$table] = $list;
        }

        return $relations;
    }

    /**
     * @param  list<string>  $primaryKey
     * @param  list<string>  $foreignKey
     */
    private static function isOneToOne(array $primaryKey, array $foreignKey): bool
    {
        return count($primaryKey) === count($foreignKey)
            && array_diff($primaryKey, $foreignKey) === [];
    }

    /**
     * The trailing non-tenant leg pair — the one column pair that is total
     * WITHIN an account — or null when the constraint carries nothing but the
     * tenant leg.
     *
     * @param  list<string>  $from
     * @param  list<string>  $to
     * @return array{0:string,1:string}|null
     */
    private static function tenantPair(array $from, array $to): ?array
    {
        $pairs = [];
        foreach ($from as $i => $column) {
            if ($column !== 'account_id' && isset($to[$i])) {
                $pairs[] = [$column, $to[$i]];
            }
        }

        return $pairs === [] ? null : (array) $pairs[count($pairs) - 1];
    }

    /**
     * Relation method name, taken from the table's own stem so the method says
     * which table it reaches and stays invertible.
     */
    private static function methodName(string $table): string
    {
        $studly = self::studlyStem($table);

        // A relation name that is also a Model method would shadow Eloquent's
        // own API, so those fall back to an explicitly namespaced name rather
        // than being emitted at all.
        $reserved = [
            'save', 'delete', 'update', 'fresh', 'refresh', 'replicate', 'newQuery',
            'getKey', 'setAttribute', 'getAttribute', 'getTable', 'getKeyName',
            'toArray', 'toJson', 'fill', 'push', 'pop', 'truncate', 'increment',
            'decrement', 'first', 'last', 'find', 'all', 'query', 'with', 'without',
        ];
        $name = lcfirst($studly);

        return in_array($name, $reserved, true) ? 'tf'.$studly : $name;
    }

    /** @param array<string,array<string,true>> $used */
    private static function uniqueMethod(array &$used, string $table, string $name, string $fkName): string
    {
        $used[$table] ??= [];
        $candidate = $name;
        if (isset($used[$table][$candidate])) {
            // Two constraints between one pair of tables: disambiguate with the
            // constraint's own name, which the ENGINE supplied, so the
            // disambiguation is measured too rather than a counter.
            $candidate = $name.'_'.$fkName;
        }
        $used[$table][$candidate] = true;

        return $candidate;
    }

    /**
     * @param  list<array{method:string,signature:string,body:string,doc:string}>  $relations
     */
    private function renderModel(
        MirrorShape $shape,
        string $table,
        string $class,
        array $relations,
    ): string {
        $columns = $shape->columnNames($table);
        $primaryKey = $shape->primaryKey($table);
        $base = self::baseClassFor($table);

        $body = [];

        $doc = ['/**'];
        $doc[] = ' * Eloquent model for the shipped mirror table `'.$table.'`.';
        $doc[] = ' *';
        $doc[] = ' * Columns, casts and relations are REFLECTED from the executed shipped DDL, not';
        $doc[] = ' * transcribed and not inferred from names — see ModelGenerator.';
        $doc[] = ' *';
        $doc[] = ' * A relation appears here in one of two shapes, and the docblock on each says';
        $doc[] = ' * which: one where the engine DECLARES a foreign key, and one HORIZONTAL — an';
        $doc[] = ' * entity-to-entity edge derived from an inline `{base}_type` + `{base}_id` peer';
        $doc[] = ' * pair or from a catalog-declared scalar entity column, which no constraint can';
        $doc[] = ' * express. See HorizontalEntityRelations for why those are not FKs.';
        if ($relations !== []) {
            $doc[] = ' *';
            $doc[] = ' * Relations declared on this table: '.count($relations).'.';
        }
        $doc[] = ' */';
        $body[] = implode("\n", $doc);
        $body[] = 'final class '.$class.' extends '.$base;
        $body[] = '{';
        $body[] = '    use AccountScoped;';
        $body[] = '';
        $body[] = "    protected \$table = '{$table}';";
        $body[] = '';
        $body[] = '    protected $guarded = [];';

        // --- key metadata, measured rather than assumed.
        $body[] = '';
        $body[] = '    /** No autoincrement anywhere on the mirror: keys are Telegram-native. */';
        $body[] = '    public $incrementing = false;';
        if ($primaryKey !== []) {
            $scalar = count($primaryKey) === 1
                ? $primaryKey[0]
                : $primaryKey[count($primaryKey) - 1];
            $body[] = '';
            if (count($primaryKey) === 1) {
                $body[] = "    protected \$primaryKey = '{$scalar}';";
            } else {
                $body[] = '    /**';
                $body[] = '     * Eloquent gets ONE scalar key. The real key is the composite ('.implode(', ', $primaryKey).'),';
                $body[] = '     * which the database enforces; `'.$scalar.'` is total only WITHIN an account, and';
                $body[] = '     * AccountScope guarantees that. So `find()` is account-scoped by construction:';
                $body[] = '     * reach for a relation or an explicit where, never a bare find().';
                $body[] = '     */';
                $body[] = "    protected \$primaryKey = '{$scalar}';";
            }
        }

        $timestamps = in_array('created_at', $columns, true) && in_array('updated_at', $columns, true);
        $body[] = '';
        $body[] = '    public $timestamps = '.($timestamps ? 'true' : 'false').';';

        // --- casts, one per column whose executed type maps to one.
        $casts = [];
        foreach ($columns as $column) {
            $cast = self::castFor((string) $shape->columnType($table, $column));
            if ($cast !== null) {
                $casts[$column] = $cast;
            }
        }
        $body[] = '';
        if ($casts === []) {
            $body[] = '    /** No column type on this table maps to an Eloquent cast. */';
            $body[] = '    protected $casts = [];';
        } else {
            $body[] = '    /**';
            $body[] = '     * Derived from the type the ENGINE reports for each column — never from a';
            $body[] = '     * docblock, and never from SQLite, whose affinity collapses integer(),';
            $body[] = '     * bigInteger() and tinyInteger() alike and drops width and signedness.';
            $body[] = '     *';
            $body[] = '     * @var array<string,string>';
            $body[] = '     */';
            $body[] = '    protected $casts = [';
            foreach ($casts as $column => $cast) {
                $body[] = "        '{$column}' => '{$cast}',";
            }
            $body[] = '    ];';
        }

        $needsPeerTrait = false;
        foreach ($relations as $relation) {
            // The two flags are DERIVED from the body rather than read off the
            // entry. The body is the ground truth actually emitted, so deriving
            // keeps the render step from having to trust a flag that a
            // declared-FK entry simply does not carry.
            //
            // `entityPeer()` is the only relation that is BOTH nullable and
            // peer-trait-bearing: it is the only body that can return null
            // (discriminator 0 — Telegram sent no peer) and the only one that
            // calls the trait's resolver. Every other body is a plain
            // HasMany/HasOne/BelongsTo that always yields a relation.
            $isPeerTrait = str_contains((string) $relation['body'], 'entityPeer(');

            $body[] = '';
            $body[] = '    /** '.$relation['doc'].' */';
            $body[] = '    public function '.$relation['method'].'(): '
                .($isPeerTrait ? '?' : '').$relation['signature'];
            $body[] = '    {';
            $body[] = '        '.$relation['body'];
            $body[] = '    }';

            $needsPeerTrait = $needsPeerTrait || $isPeerTrait;
        }

        $body[] = '}';

        // The peer trait is emitted PER MODEL, only where an inline peer pair
        // actually exists, rather than on the two shared bases
        // (`TfMirrorModel` / `TlAnchorModel`) — a base is not this slice's to
        // change, and most of the 257 models carry no peer pair at all.
        //
        // Index 3 is directly after `use AccountScoped;`: `renderModel()` has
        // pushed the class line, `{`, the trait line and a blank, in that order,
        // immediately above.
        if ($needsPeerTrait) {
            array_splice($body, 3, 0, ['    use ResolvesEntityPeer;', '']);
        }

        $imports = [
            'MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped',
            'MeRezaRezaei\Teleframe\Schema\Eloquent\\'.$base,
        ];
        if ($needsPeerTrait) {
            $imports[] = 'MeRezaRezaei\Teleframe\Mirror\Models\ResolvesEntityPeer';
        }
        foreach ($relations as $relation) {
            $imports[] = 'Illuminate\Database\Eloquent\Relations\\'.$relation['signature'];
        }

        return CodeWriter::phpFile(self::NS, [...self::useLines($imports), ...$body]);
    }

    /**
     * Anchor tables keep `TlAnchorModel`; every other mirror table is a
     * `TfMirrorModel`. Read off {@see Naming}'s own domain model map, so the
     * anchor set cannot drift from the naming rules it mirrors, and not from a
     * list of table names.
     */
    private static function baseClassFor(string $table): string
    {
        foreach (self::anchorDomains() as $domain) {
            if (Naming::domainTable($domain) === $table) {
                return 'TlAnchorModel';
            }
        }

        return 'TfMirrorModel';
    }

    /**
     * The TL domains that own a generated anchor model, taken from the same
     * map {@see Naming::domainModel()} reads, so the two cannot disagree.
     *
     * @return list<string>
     */
    private static function anchorDomains(): array
    {
        return Naming::domainModelNames();
    }

    /**
     * Per-file use-import assembly: deduped, ksort-deterministic 'use X;'
     * lines terminated by a single blank line.
     *
     * @param  list<string>  $imports  fully-qualified class names
     * @return list<string> deduped 'use X;' lines in ksort order plus a blank separator
     */
    private static function useLines(array $imports): array
    {
        $set = [];
        foreach ($imports as $import) {
            if ($import !== '') {
                $set[$import] = true;
            }
        }
        ksort($set);
        $lines = [];
        foreach (array_keys($set) as $fqcn) {
            $lines[] = 'use '.$fqcn.';';
        }
        $lines[] = '';

        return $lines;
    }
}
