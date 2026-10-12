<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

use FilesystemIterator;
use MeRezaRezaei\Teleframe\DataCore\Declaration\DataDeclaration;
use MeRezaRezaei\Teleframe\DataCore\Declaration\DeclarationRegistry;
use MeRezaRezaei\Teleframe\DataCore\Declarations\TlMirrorDeclarationFactory;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorShape;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Regeneration engine (spec §7): loads the metamodel from teleframe's
 * committed schema sources (Task 2 input seam), merges them into one
 * combined scheme (later files win ctor-name collisions), runs the N-pass
 * generator pipeline (Models/Data/Factories + declaration-driven
 * Validators/Requests/Events), and writes the deterministic manifest gate.
 */
final class SchemaRegenerator
{
    /** Curated migration dial default (plan Task 4): TL namespaces shipped to src/Laravel/Migrations/. */
    public const DEFAULT_SHIP_NAMESPACES = ['auth', 'messages', 'users', 'channels', 'updates', 'help', 'contacts'];

    private bool $force = false;

    /** @var list<string>|null null = do not ship; list = copy these namespaces' migrations to <out>/src/Laravel/Migrations */
    private ?array $shipNamespaces = null;

    public function force(bool $force = true): self
    {
        $this->force = $force;

        return $this;
    }

    /** @param list<string>|null $namespaces */
    public function shipNamespaces(?array $namespaces): self
    {
        $this->shipNamespaces = $namespaces;

        return $this;
    }

    /**
     * Metamodel from teleframe's committed sources (plan Task 2): the dir
     * defaults to config('teleframe.schema_sources') / the vendored
     * teleframe path and can be overridden explicitly (tests, --schemas).
     */
    public function loadScheme(?string $sourcesDir = null): TlScheme
    {
        $dir = $sourcesDir ?? TeleframeSchemeLoader::defaultSourcesDir();
        if (! is_dir($dir)) {
            throw new TlRegenerateException("teleframe schema sources dir not found: {$dir}");
        }
        $files = glob(rtrim($dir, '/').'/*.tl');
        if ($files === false) {
            throw new TlRegenerateException("failed to read scheme dir: {$dir}");
        }
        sort($files);
        if ($files === []) {
            throw new TlRegenerateException("no .tl scheme files in {$dir}");
        }

        return $this->parseAll($files);
    }

    /**
     * Mirror-shape artifact path. Overridable so a caller (or a test proving
     * the refusal path) can point at a different measurement; null means the
     * committed artifact.
     */
    private ?string $mirrorShapePath = null;

    /** @param string|null $path null = the committed artifact */
    public function mirrorShapePath(?string $path): self
    {
        $this->mirrorShapePath = $path;

        return $this;
    }

    /**
     * The reflected shape of the shipped DDL, or a refusal.
     *
     * `artisan teleframe:regenerate` cannot be the thing that boots MySQL to
     * measure the shape (the dial is 55 migrations and ~100s against a live
     * server), so it reads back the artifact `bin/generate-mirror-shape.php`
     * measured. That makes the artifact a HARD PRECONDITION rather than an
     * optional input: without it there is no producer for the mirror model
     * layer, and the run would otherwise wipe the tree and emit only the
     * scheme-only anchor projection — a silent loss of 253 files.
     *
     * So this throws rather than degrades. `--force` does not bypass it:
     * force means "I accept the constructor-count drift", not "delete files
     * whose producer I could not run".
     */
    private function mirrorShape(): MirrorShape
    {
        $path = $this->mirrorShapePath ?? MirrorShape::artifactPath();
        if (! is_file($path)) {
            throw new TlRegenerateException(sprintf(
                'refusing to regenerate: the mirror shape artifact is missing (%s).'
                .' The mirror model layer is generated FROM it, and without it this run'
                .' would delete %s and emit only the scheme-only anchor projection.'
                .' Produce it with: php bin/generate-mirror-shape.php --write-models',
                $path,
                'src/Schema/Generated/Models',
            ));
        }

        try {
            return MirrorShape::load($path);
        } catch (RuntimeException $e) {
            throw new TlRegenerateException(sprintf(
                'refusing to regenerate: the mirror shape artifact at %s is unreadable or has no `tables` object (%s).'
                .' Produce it with: php bin/generate-mirror-shape.php --write-models',
                $path,
                $e->getMessage(),
            ), 0, $e);
        }
    }

    /**
     * @return array{counts: array{types:int,constructors:int,methods:int,files:int,tables:int,fks:int,validators:int,requests:int,events:int,models:int,model_relations:int,mirror_tables:int}, manifest: array, ship?: array{namespaces:list<string>,count:int,dir:string,left:list<string>}}
     */
    public function regenerate(string $schemasDir, string $outputDir): array
    {
        if (! is_dir($schemasDir)) {
            throw new TlRegenerateException("schemas dir not found: {$schemasDir}");
        }

        $files = glob(rtrim($schemasDir, '/').'/*.tl');
        if ($files === false) {
            throw new TlRegenerateException("failed to read schemas dir: {$schemasDir}");
        }
        $tdl = glob(rtrim($schemasDir, '/').'/*.tdl');
        if ($tdl === false) {
            throw new TlRegenerateException("failed to read schemas dir: {$schemasDir}");
        }
        $files = array_merge($files, $tdl);
        sort($files);
        if ($files === []) {
            throw new TlRegenerateException("no .tl/.tdl scheme files in {$schemasDir}");
        }

        $combined = $this->parseAll($files);
        $counts = $combined->counts();
        $counts['files'] = count($files);
        $schemeFiles = $files; // line 141 writes sources; keep the scheme list out of the bucket-loop scope

        $this->gate($combined, $outputDir);

        // The mirror model layer is generated from the REFLECTED shape of the
        // shipped DDL, not from the TL scheme. The scheme carries no columns
        // and no foreign keys, so a Models pass constructed without this emits
        // only the scheme-only anchor projection (12 files, 0 relations) over a
        // tree that holds one model per mirror table — which is precisely how
        // a regeneration used to delete 253 models and report success.
        //
        // Refuses (not degrades) when the artifact is unavailable, so the
        // wipe below is unreachable without a producer for what it wipes.
        $shape = $this->mirrorShape();

        // N-pass pipeline (data-core plan §3): the extension hook is exactly
        // this list — pass class => target dir under src/Schema/Generated/.
        // The declaration-driven emitters (Validator|Request|EventGenerator)
        // receive the merged DeclarationRegistry in their constructor and dial
        // their own emission surface; the legacy generators take no args and
        // keep their existing generate(TlScheme) contract. ModelGenerator is
        // the one pass that takes the MirrorShape instead.
        $registry = $this->declarations($combined);

        $buckets['Models'] = (new ModelGenerator($shape))->generate($combined);

        /** @var list<array{0:class-string,1:string,2?:DeclarationRegistry}> $passes */
        $passes = [
            [DtoGenerator::class, 'Data'],
            [FactoryGenerator::class, 'Factories'],
            [ValidatorGenerator::class, 'Validators', $registry],
            [RequestGenerator::class, 'Requests', $registry],
            [EventGenerator::class, 'Events', $registry],
        ];
        foreach ($passes as $pass) {
            $class = $pass[0];
            $generator = isset($pass[2]) ? new $class($pass[2]) : new $class;
            $buckets[$pass[1]] = $generator->generate($combined);
        }
        ksort($buckets);

        $mirrorTables = count($shape->tableNames());
        $modelRelations = self::countRelations($buckets['Models']);

        // The coverage gate, BEFORE the wipe. See gateMirrorCoverage() for the
        // population it compares and why a constructor count cannot stand in
        // for it.
        $this->gateMirrorCoverage($shape, $buckets['Models'], $modelRelations, $outputDir);

        $this->wipe($outputDir);
        foreach ($buckets as $dir => $files) {
            $this->writeAll($outputDir.'/src/Schema/Generated/'.$dir, $files);
        }

        $counts['tables'] = 0;
        $counts['fks'] = 0;
        $counts['validators'] = count($buckets['Validators']);
        $counts['requests'] = count($buckets['Requests']);
        $counts['events'] = count($buckets['Events']);
        // The mirror layer's own population, recorded so the manifest states
        // what the Models tree is rather than leaving it inferable only from a
        // file count. `tables`/`fks` keep their post-purge meaning (nothing
        // derived from the scheme); these three are the measured ones.
        $counts['models'] = count($buckets['Models']);
        $counts['model_relations'] = $modelRelations;
        $counts['mirror_tables'] = $mirrorTables;

        $manifest = Manifest::build(
            $combined->layer,
            $counts,
            [],
            0,
            $combined->crcMismatches,
        );
        $manifest['sources'] = array_map('basename', $schemeFiles);
        $manifest['hash'] = Manifest::hash($manifest);
        file_put_contents($outputDir.'/src/Schema/Generated/schema-manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");

        $result = ['counts' => $counts, 'manifest' => $manifest];
        if ($this->shipNamespaces !== null) {
            $result['ship'] = $this->shipMigrations($combined, [], $outputDir);
        }

        return $result;
    }

    /**
     * Curated migration dial (plan Task 4): copy the per-type migration
     * files whose TL type namespace is on the dial from the freshly
     * generated full set into <out>/src/Laravel/Migrations (byte-identical
     * copies — the publishable surface; provider loadMigrationsFrom).
     * Namespace membership is read from the scheme (TlType::namespace()),
     * never parsed back out of filenames — root-namespace types (User,
     * Chat, Updates, ...) and the cross-namespace route/FK monolith files
     * stay in the full src/Schema/Generated/ set only.
     *
     * @param  array<string,string>  $tableMap  constructor/child/route table => migration filename
     * @param  array<string,string>  $migFiles  migration filename => content
     * @return array{namespaces:list<string>,count:int,dir:string,left:list<string>}
     */
    /** Ship-exempt migrations the owning app checks in (ShipDialGoldenTest
     *  mirrors this contract): the ship purge must NEVER delete them.
     *  Mirrors ShipDialGoldenTest::APP_OWNED_MIGRATIONS plus the W-04
     *  victim tf_accounts migration (W-04 finding e: register the filename
     *  before merge). */
    private const APP_OWNED_MIGRATIONS = [
        '2026_09_08_000100_create_tl_user_bindings_table.php',
        '2026_09_09_000200_create_telegram_apps_table.php',
        '2026_09_09_000201_create_telegram_accounts_table.php',
        '2026_09_14_000000_add_user_id_to_telegram_accounts_table.php',
        '2026_09_14_000001_create_tg_update_routing_table.php',
        '2026_09_22_000100_create_tf_accounts_table.php',
        // W-04 finding e again, on the mirror's coverage ledger
        // (2026_10_02_000500). Hand-authored LOCAL/APP state with no generated
        // source, so the classify loop at shipMigrations() falls through to the
        // "unrecognized hand-authored" arm for it unless it is allow-listed
        // here: every teleframe:regenerate would then warn
        // "leaving unrecognized hand-authored migration in place" about a file
        // that is intentional. Registered in the same commit as its test-side
        // mirror, ShipDialGoldenTest::APP_OWNED_MIGRATIONS, which is the half
        // that pins it as deliberate rather than merely spared.
        '2026_10_02_000500_create_tf_ingest_clues_table.php',
    ];

    /** Substring of CodeWriter::BANNER ('// GENERATED — do not edit; run
     *  artisan teleframe:regenerate') that marks a file as a regenerable
     *  pipeline artifact. A hand-authored migration never carries it and
     *  must never be purged (W-04): unmatched files are warned + left. */
    private const GENERATED_BANNER = 'GENERATED — do not edit';

    private bool $pruneStaleGenerated = false;

    /** W-04: opt-in sweep of stale GENERATED-banner migrations left in the
     *  ship dir. Default (false) never deletes any file outside the ship
     *  set or allow-list — it warns loudly and leaves them in place. */
    public function prune(bool $prune = true): self
    {
        $this->pruneStaleGenerated = $prune;

        return $this;
    }

    /** Safety gate: refuse to wipe the generated tree when the ctor count
     *  drifts by more than this ratio vs. the committed manifest (pass
     *  --force to override). */
    private const MAX_CTOR_DRIFT_RATIO = 0.30;

    private function shipMigrations(TlScheme $combined, array $migFiles, string $outputDir): array
    {
        $dir = $outputDir.'/src/Laravel/Migrations';
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        // Safe purge (W-04): never a wholesale removeTree — classify each
        // existing file: ship-set rewrite > allow-listed preserve > stale
        // generated (banner) unlink ONLY when prune() opted in >
        // unrecognized hand-authored warn+leave. Default: anything unmatched
        // is warned loudly and left in place; nothing outside the ship set
        // or allow-list is ever silently deleted.
        $left = [];
        $existing = glob($dir.'/*.php') ?: [];
        foreach ($existing as $file) {
            $name = basename($file);
            if (isset($migFiles[$name])) {
                file_put_contents($file, $migFiles[$name]);
                unset($migFiles[$name]);
            } elseif (in_array($name, self::APP_OWNED_MIGRATIONS, true)) {
                continue;
            } elseif (str_contains((string) file_get_contents($file), self::GENERATED_BANNER)) {
                if ($this->pruneStaleGenerated) {
                    unlink($file);
                } else {
                    $left[] = $name;
                    error_log(sprintf(
                        '[teleframe][W-04] leaving GENERATED stale migration in place (pass --prune to sweep regenerable artifacts): %s',
                        $name,
                    ), 4);
                }
            } else {
                $left[] = $name;
                error_log(sprintf(
                    '[teleframe][W-04] leaving unrecognized hand-authored migration in place (not in ship set, not allow-listed, no GENERATED banner): %s',
                    $name,
                ), 4);
            }
        }

        // Any freshly shipped entries not already on disk.
        $shipped = [];
        ksort($migFiles);
        foreach ($migFiles as $name => $content) {
            $shipped[$name] = $content;
            file_put_contents($dir.'/'.$name, $content);
        }
        sort($left);

        return [
            'namespaces' => $this->shipNamespaces ?? [],
            'count' => count($shipped),
            'dir' => $dir,
            'left' => $left,
        ];
    }

    /** @param list<string> $files */
    private function parseAll(array $files): TlScheme
    {
        $layer = 0;
        foreach ($files as $file) {
            $layer = max($layer, TeleframeSchemeLoader::layerFromFile($file));
        }
        $combined = new TlScheme($layer);
        foreach ($files as $file) {
            $scheme = TeleframeSchemeLoader::parseFile($file);
            $layer = max($layer, $scheme->layer);
            foreach ($scheme->types() as $type) {
                foreach ($type->constructors() as $ctor) {
                    $existing = $combined->types()[$ctor->resultType] ?? null;
                    if ($existing !== null && isset($existing->constructors()[$ctor->name])) {
                        $combined->removeConstructor($ctor->name);
                    }
                    $combined->addConstructor($ctor);
                }
            }
            foreach ($scheme->methods() as $method) {
                $combined->addMethod($method);
            }
            foreach ($scheme->crcMismatches as $name => $mm) {
                $combined->crcMismatches[$name] = $mm;
            }
        }

        return $combined;
    }

    /**
     * Data-core plan §3 bridge: ONE merged DeclarationRegistry per regen
     * run — the tl-mirror declarations (one DataDeclaration per shipped TL
     * type via TlMirrorDeclarationFactory::fromScheme) merged with the
     * auto-discovered business declarations
     * (src/Teleframe/DataCore/Declarations/*Declaration.php). Business wins
     * on a name collision (the operator edits the knowledge graph); the
     * merged name map is ksort'ed so discovery/merge order can never leak
     * into the registry.
     */
    private function declarations(TlScheme $scheme): DeclarationRegistry
    {
        $fromMirror = TlMirrorDeclarationFactory::fromScheme($scheme)->all();

        $declarations = [];
        foreach ($fromMirror as $entry) {
            $declarations[$entry->name] = $entry;
        }
        foreach (self::businessDeclarations() as $name => $declaration) {
            $declarations[$name] = $declaration;
        }
        ksort($declarations);

        return DeclarationRegistry::fromDeclarations(array_values($declarations));
    }

    /**
     * Auto-discovered business declarations: every *Declaration.php under
     * src/Teleframe/DataCore/Declarations/ exposing public static
     * define(): DataDeclaration. Files are sorted before discovery and the
     * result map is ksort'ed — deterministic regardless of filesystem
     * read order. The dir does not exist pre-wave: empty map, no error.
     *
     * @return array<string, DataDeclaration>
     */
    private static function businessDeclarations(): array
    {
        $dir = dirname(__DIR__, 3).'/src/Teleframe/DataCore/Declarations';
        if (! is_dir($dir)) {
            return [];
        }
        $files = glob($dir.'/*Declaration.php');
        if ($files === false) {
            return [];
        }
        sort($files);

        $out = [];
        foreach ($files as $file) {
            $class = 'MeRezaRezaei\Teleframe\DataCore\Declarations\\'.substr(basename($file), 0, -4);
            if (! class_exists($class) || ! is_callable([$class, 'define'])) {
                continue;
            }
            $declaration = $class::define();
            if (! $declaration instanceof DataDeclaration) {
                throw new TlRegenerateException(sprintf(
                    'business declaration %s::define() must return %s, got %s',
                    $class,
                    DataDeclaration::class,
                    get_debug_type($declaration),
                ));
            }
            $out[$declaration->name] = $declaration;
        }
        ksort($out);

        return $out;
    }

    private function gate(TlScheme $combined, string $outputDir): void
    {
        $manifestPath = $outputDir.'/src/Schema/Generated/schema-manifest.json';
        if ($this->force || ! is_file($manifestPath)) {
            return;
        }
        $prev = json_decode((string) file_get_contents($manifestPath), true);
        $prevCtors = (int) ($prev['counts']['constructors'] ?? 0);
        if ($prevCtors === 0) {
            return;
        }
        $now = $combined->counts()['constructors'];
        if (abs($now - $prevCtors) / $prevCtors > self::MAX_CTOR_DRIFT_RATIO) {
            throw new TlRegenerateException(sprintf(
                'constructor count changed by more than %.0f%% (%d -> %d); pass --force if intentional',
                self::MAX_CTOR_DRIFT_RATIO * 100,
                $prevCtors,
                $now,
            ));
        }
    }

    /**
     * Relation count over an emitted Models bucket, measured the same way
     * `bin/generate-mirror-shape.php` measures it: a relation is a body line
     * beginning `return $this->hasMany(`, `hasOne(` or `belongsTo(`.
     *
     * @param  array<string,string>  $models
     */
    private static function countRelations(array $models): int
    {
        $relations = 0;
        foreach ($models as $content) {
            foreach (explode("\n", $content) as $line) {
                $trimmed = ltrim($line);
                if (str_starts_with($trimmed, 'return $this->hasMany(')
                    || str_starts_with($trimmed, 'return $this->hasOne(')
                    || str_starts_with($trimmed, 'return $this->belongsTo(')
                ) {
                    $relations++;
                }
            }
        }

        return $relations;
    }

    /**
     * THE POPULATION THIS GATE COMPARES, stated so nobody has to guess:
     *
     *   A. the mirror tables in the measured shape  (`MirrorShape::tableNames()`)
     *   B. the model files this run is about to write (`count($models)`)
     *   C. the model files currently on disk in the output tree
     *      (`glob(<out>/src/Schema/Generated/Models/*.php)`)
     *
     * A is the AUTHORITY: the generator's own docblock states the table→class
     * map is a bijection onto the reflected table names, so A and B must be
     * EQUAL — not merely close, not within a drift band. Any shortfall means
     * the Models pass was not driven by the shape, which is the defect this
     * gate exists to catch.
     *
     * WHY A CONSTRUCTOR COUNT CANNOT STAND IN. The pre-existing gate compares
     * TL constructor counts against the previous manifest, ±30%. That
     * population is unaffected by the mirror layer: the mirror is measured
     * from executed DDL, not from the scheme, so a run that wiped 253 mirror
     * models and rewrote 12 anchors changed the constructor count by ZERO and
     * sailed through the gate. A gate that cannot tell 265 mirror tables from
     * 11 anchors is not a gate.
     *
     * WHAT MAKES IT REFUSE (any one of these):
     *
     *   1. `B < A` — the run cannot reproduce the mirror layer. Today B is 12
     *      and A is 265: exactly the landmine. Refuses.
     *   2. `C > B` — the tree on disk holds MORE model files than this run
     *      emits, so the wipe would DELETE files nothing recreates. This is
     *      the check that makes the refusal hold even if the shape is
     *      deliberately narrowed: 265 on disk vs 11 emitted refuses. Refuses
     *      regardless of `--force`, because force means "I accept the
     *      constructor-count drift", not "delete files whose producer did not
     *      run".
     *   3. `A > 0 && relations === 0` — a shape that declares in-layer foreign
     *      keys must yield relations. The anchor-only projection declares none,
     *      which is how coverage collapsed to 0 relations without an error.
     *
     * A DEGENERATE SHAPE is the fourth refusal: an artifact that parses but
     * measures zero tables would satisfy `B == A == 0` and let the wipe
     * proceed with an empty Models bucket. Zero tables is never a legitimate
     * measurement of a 265-table dial.
     *
     * @param  array<string,string>  $models
     */
    private function gateMirrorCoverage(MirrorShape $shape, array $models, int $relations, string $outputDir): void
    {
        $mirrorTables = count($shape->tableNames());
        $emitted = count($models);
        $modelDir = $outputDir.'/src/Schema/Generated/Models';
        $onDisk = count(glob($modelDir.'/*.php') ?: []);

        if ($mirrorTables === 0) {
            throw new TlRegenerateException(
                'refusing to regenerate: the mirror shape measures 0 tables.'
                .' A degenerate shape would pass an equality check against an empty'
                .' Models bucket and let the wipe delete the model layer.'
                .' Re-measure with: php bin/generate-mirror-shape.php --write-models'
            );
        }

        if ($emitted < $mirrorTables) {
            throw new TlRegenerateException(sprintf(
                'refusing to wipe: the Models pass emitted %d model file(s) for %d mirror table(s).'
                .' The table→class map is a bijection onto the reflected tables, so this is a shortfall of %d.'
                .' Wiping now would delete model files this run cannot reproduce.'
                .' Produce the artifact with: php bin/generate-mirror-shape.php --write-models',
                $emitted,
                $mirrorTables,
                $mirrorTables - $emitted,
            ));
        }

        if ($onDisk > $emitted) {
            throw new TlRegenerateException(sprintf(
                'refusing to wipe: src/Schema/Generated/Models holds %d model file(s) but this run emits only %d.'
                .' Wiping would delete %d file(s) that nothing in this run recreates.'
                .' Re-measure the artifact with: php bin/generate-mirror-shape.php --write-models',
                $onDisk,
                $emitted,
                $onDisk - $emitted,
            ));
        }

        $inLayer = 0;
        foreach ($shape->tableNames() as $table) {
            foreach ($shape->foreignKeys($table) as $fk) {
                if ($shape->has($fk['to_table'])) {
                    $inLayer++;
                }
            }
        }

        if ($inLayer > 0 && $relations === 0) {
            throw new TlRegenerateException(sprintf(
                'refusing to wipe: the shape declares %d in-layer foreign key(s) but the Models pass emitted 0 relations.'
                .' Relations come only from declared constraints, so a 0 here means the Models pass'
                .' was not driven by the shape.',
                $inLayer,
            ));
        }
    }

    private function wipe(string $outputDir): void
    {
        foreach (['migrations', 'Models', 'Data', 'Factories', 'Validators', 'Requests', 'Events'] as $dir) {
            self::removeTree($outputDir.'/src/Schema/Generated/'.$dir);
        }
        $manifest = $outputDir.'/src/Schema/Generated/schema-manifest.json';
        if (is_file($manifest)) {
            unlink($manifest);
        }
        if (! is_dir($outputDir.'/src/Schema/Generated')) {
            mkdir($outputDir.'/src/Schema/Generated', 0775, true);
        }
    }

    /**
     * Shell-free recursive delete (night W4 safety): CHILD_FIRST order
     * unlinks files before their directories rmdir; SKIP_DOTS guards
     * '.'/'..' self-recursion; symlinks unlink, never traverse. The
     * companion mkdirs use 0775 — these are dev-artifact trees:
     * group-writable, not world-writable.
     */
    private static function removeTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            if ($item->isLink() || ! $item->isDir()) {
                unlink($item->getPathname());
            } else {
                rmdir($item->getPathname());
            }
        }
        rmdir($dir);
    }

    /** @param array<string,string> $files */
    private function writeAll(string $dir, array $files): void
    {
        foreach ($files as $name => $content) {
            $path = $dir.'/'.$name;
            @mkdir(dirname($path), 0775, true);
            file_put_contents($path, $content);
        }
    }
}
