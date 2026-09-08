<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Regeneration engine (spec §7): loads the metamodel from teleframe's
 * committed schema sources (Task 2 input seam), merges them into one
 * combined scheme (later files win ctor-name collisions), runs all four
 * generators, and writes the deterministic manifest gate.
 */
final class SchemaRegenerator
{
    /** Curated migration dial default (plan Task 4): TL namespaces shipped to migrations/. */
    public const DEFAULT_SHIP_NAMESPACES = ['auth', 'messages', 'users', 'channels', 'updates', 'help', 'contacts'];

    private bool $force = false;

    /** @var list<string>|null null = do not ship; list = copy these namespaces' migrations to <out>/migrations */
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
        if (!is_dir($dir)) {
            throw new TlRegenerateException("teleframe schema sources dir not found: {$dir}");
        }
        $files = glob(rtrim($dir, '/') . '/*.tl') ?: [];
        sort($files);
        if ($files === []) {
            throw new TlRegenerateException("no .tl scheme files in {$dir}");
        }
        return $this->parseAll($files);
    }

    /**
     * @return array{counts: array{types:int,constructors:int,methods:int,files:int,tables:int,fks:int}, manifest: array, ship?: array{namespaces:list<string>,count:int,dir:string}}
     */
    public function regenerate(string $schemasDir, string $outputDir): array
    {
        if (!is_dir($schemasDir)) {
            throw new TlRegenerateException("schemas dir not found: {$schemasDir}");
        }

        $files = glob(rtrim($schemasDir, '/') . '/*.tl') ?: [];
        $tdl = glob(rtrim($schemasDir, '/') . '/*.tdl') ?: [];
        $files = array_merge($files, $tdl);
        sort($files);
        if ($files === []) {
            throw new TlRegenerateException("no .tl/.tdl scheme files in {$schemasDir}");
        }

        $combined = $this->parseAll($files);
        $counts = $combined->counts();
        $counts['files'] = count($files);

        $this->gate($combined, $outputDir);

        $mig = new MigrationGenerator();
        $migFiles = $mig->generate($combined);
        $modelFiles = (new ModelGenerator())->generate($combined);
        $dtoFiles = (new DtoGenerator())->generate($combined);
        $factoryFiles = (new FactoryGenerator())->generate($combined);

        $this->wipe($outputDir);
        $this->writeAll($outputDir . '/generated/migrations', $migFiles);
        $this->writeAll($outputDir . '/generated/Models', $modelFiles);
        $this->writeAll($outputDir . '/generated/Data', $dtoFiles);
        $this->writeAll($outputDir . '/generated/Factories', $factoryFiles);

        $stats = $mig->stats();
        $counts['tables'] = count($stats['tables']);
        $counts['fks'] = $stats['fk_count'];

        $manifest = Manifest::build(
            $combined->layer,
            $counts,
            $stats['tables'],
            $stats['fk_count'],
            $combined->crcMismatches,
        );
        $manifest['sources'] = array_map('basename', $files);
        $manifest['hash'] = Manifest::hash($manifest);
        file_put_contents($outputDir . '/generated/schema-manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");

        $result = ['counts' => $counts, 'manifest' => $manifest];
        if ($this->shipNamespaces !== null) {
            $result['ship'] = $this->shipMigrations($combined, $stats['tables'], $migFiles, $outputDir);
        }
        return $result;
    }

    /**
     * Curated migration dial (plan Task 4): copy the per-type migration
     * files whose TL type namespace is on the dial from the freshly
     * generated full set into <out>/migrations (byte-identical copies —
     * the publishable surface; provider loadMigrationsFrom). Namespace
     * membership is read from the scheme (TlType::namespace()), never
     * parsed back out of filenames — root-namespace types (User, Chat,
     * Updates, ...) and the cross-namespace route/FK monolith files stay
     * in the full generated/ set only.
     *
     * @param array<string,string> $tableMap anchor/instance/child table => migration filename
     * @param array<string,string> $migFiles migration filename => content
     * @return array{namespaces:list<string>,count:int,dir:string}
     */
    private function shipMigrations(TlScheme $combined, array $tableMap, array $migFiles, string $outputDir): array
    {
        $dir = $outputDir . '/migrations';
        self::removeTree($dir);
        mkdir($dir, 0775, true);

        $shipped = [];
        foreach ($combined->types() as $type) {
            if ($type->name === 'Vector t' || $type->constructors() === []) {
                continue; // mirrors MigrationGenerator's file-emission loop
            }
            if (!in_array($type->namespace(), $this->shipNamespaces ?? [], true)) {
                continue;
            }
            $file = $tableMap[Naming::anchorTable($type->name)] ?? null;
            if ($file !== null && isset($migFiles[$file])) {
                $shipped[$file] = $migFiles[$file];
            }
        }
        ksort($shipped);
        foreach ($shipped as $name => $content) {
            file_put_contents($dir . '/' . $name, $content);
        }
        return ['namespaces' => $this->shipNamespaces ?? [], 'count' => count($shipped), 'dir' => $dir];
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

    private function gate(TlScheme $combined, string $outputDir): void
    {
        $manifestPath = $outputDir . '/generated/schema-manifest.json';
        if ($this->force || !is_file($manifestPath)) {
            return;
        }
        $prev = json_decode((string) file_get_contents($manifestPath), true);
        $prevCtors = (int) ($prev['counts']['constructors'] ?? 0);
        if ($prevCtors === 0) {
            return;
        }
        $now = $combined->counts()['constructors'];
        if (abs($now - $prevCtors) / $prevCtors > 0.30) {
            throw new TlRegenerateException(sprintf(
                'constructor count changed by more than 30%% (%d -> %d); pass --force if intentional',
                $prevCtors,
                $now,
            ));
        }
    }

    private function wipe(string $outputDir): void
    {
        foreach (['migrations', 'Models', 'Data', 'Factories'] as $dir) {
            self::removeTree($outputDir . '/generated/' . $dir);
        }
        $manifest = $outputDir . '/generated/schema-manifest.json';
        if (is_file($manifest)) {
            unlink($manifest);
        }
        if (!is_dir($outputDir . '/generated')) {
            mkdir($outputDir . '/generated', 0775, true);
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
        if (!is_dir($dir)) {
            return;
        }
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            if ($item->isLink() || !$item->isDir()) {
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
            $path = $dir . '/' . $name;
            @mkdir(dirname($path), 0775, true);
            file_put_contents($path, $content);
        }
    }
}
