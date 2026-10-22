<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Mirror;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use SplFileInfo;
use Throwable;

/**
 * The gate for the friction class that took every mirror write down.
 *
 * THE INCIDENT. `src/Laravel/MirrorIngesterFactory::modelClassFor()` enumerates
 * `src/Teleframe/Mirror/Models/*.php`, turns each filename into an FQCN and
 * instantiates it to read `getTable()`. Its guard was `class_exists($class)`.
 * That guard LOOKS sufficient and is not: `class_exists()` is FALSE for a trait
 * (which is why the three parked concerns were skipped — false confidence) but
 * TRUE for an ordinary class, and the directory holds one on purpose,
 * `UnmappedEntityPeer extends RuntimeException`. So `new UnmappedEntityPeer()`
 * happened, `->getTable()` fataled, and the fatal was inside the boot of every
 * mirror write. The fix was `is_subclass_of($class, Model::class)`.
 *
 * WHY A GATE AND NOT THE FIX. The fix repaired ONE instance. The instance is
 * not the defect; the CLASS is: code enumerates a directory of classes and
 * applies a guard that reads as sufficient but does not discriminate. Nothing
 * in `src/` checked that property anywhere, so the same shape was free to
 * recur in the next directory enumerator written.
 *
 * WHAT THIS TEST DOES, IN GENERAL. It does not know about the Mirror factory.
 * It DISCOVERS every directory-of-classes enumerator in `src/` — a loop whose
 * source is `glob`/`scandir`/`DirectoryIterator` (called directly, or through a
 * variable assigned one) and whose body builds a class name out of the
 * filename and then consumes it — and for each one asks two questions:
 *
 *   1. Does the guard DISCIMINATE? An existence predicate (`class_exists` and
 *      friends) is FALSE for a trait, which is exactly why an existence-only
 *      guard over a directory of models reads as sufficient while admitting
 *      any ordinary class parked there. A guard with no kind predicate at all
 *      over a directory that holds such a class is a latent fatal, and is
 *      failed by name even while nothing is admitted yet.
 *   2. Does what the guard ADMIT work? The guard is re-read out of the source
 *      (comments stripped, `use` aliases resolved) and re-evaluated for real
 *      against the real directory contents; the classes it admits are then put
 *      through the operation the loop body performs on them. For the model
 *      enumerator that operation is `new $class` + `getTable()` — the exact
 *      pair that fataled. The requirement is derived from the CONSUMER, never
 *      from the guard, so the check cannot be satisfied by restating the guard.
 *
 * Three properties this gate deliberately has:
 *
 *  1. IT CAN FAIL. Reverting the guard makes it existence-only over a
 *     directory holding a concrete non-model class, and the trip-wire names it
 *     by `file:line`. Proven by planting the revert; see
 *     `.brain/evidence/2026-10-16-prevention-directory-enumerators.md`.
 *  2. IT CANNOT PASS VACUOUSLY. If discovery ever returns zero enumerators the
 *     suite FAILS — a detector that quietly stops matching is worse than no
 *     detector, because it reads as coverage.
 *  3. IT DOES NOT SILENTLY SKIP. An enumerator whose directory expression
 *     cannot be resolved, whose class-name prefix cannot be read, whose
 *     consumer operation is unmodelled, or whose directory yields no files
 *     fails by name. A lookup that did not happen is never treated as a lookup
 *     that found nothing clean.
 *
 * Provenance: `src/Laravel/MirrorIngesterFactory.php:616-644` (the enumerator
 * and the incident), `src/Teleframe/Mirror/Models/UnmappedEntityPeer.php:45`
 * (the ordinary class `class_exists()` admits),
 * `src/Schema/Generator/SchemaRegenerator.php:428-458` (the sibling
 * enumerator, whose guard is capability-based).
 */
final class DirectoryEnumeratorGuardsDiscriminateTest extends TestCase
{
    private const SRC = __DIR__.'/../../src';

    /** Sources that can enumerate a directory. */
    private const DIRECTORY_SOURCES = [
        'glob', 'scandir', 'opendir', 'readdir',
        'DirectoryIterator', 'RecursiveDirectoryIterator', 'FilesystemIterator',
        'Finder',
    ];

    /** Predicates that only test EXISTENCE. */
    private const EXISTENCE_GUARDS = [
        'class_exists', 'interface_exists', 'trait_exists', 'enum_exists',
    ];

    // ---------------------------------------------------------------- tests

    public function test_every_directory_enumerator_guard_actually_discriminates(): void
    {
        $sites = self::discover();

        self::assertNotSame(
            [],
            $sites,
            'discovery found no directory-of-classes enumerator in src/ — the detector has rotted, not the tree.',
        );

        $report = [];
        foreach ($sites as $site) {
            $report[] = self::judge($site);
        }

        fwrite(STDERR, "\n--- directory-of-classes enumerators in src/ ---\n".implode("\n", $report)."\n");

        self::assertTrue(true, 'enumerators inspected: '.count($sites));
    }

    /**
     * Every enumerator carries a type guard at all. Split out from the
     * discrimination verdict so an UNGUARDED enumerator is named as exactly
     * that — a missing guard is a different defect from a weak one.
     */
    public function test_every_directory_enumerator_carries_a_type_guard(): void
    {
        $unguarded = [];

        foreach (self::discover() as $site) {
            if ($site['existence'] === [] && $site['kinds'] === []) {
                $unguarded[] = $site['file'].':'.$site['line'].' — '.$site['dir'];
            }
        }

        self::assertSame(
            [],
            $unguarded,
            "directory-of-classes enumerator(s) with NO type guard:\n".implode("\n", $unguarded),
        );
    }

    /**
     * The complete inventory of directory-source call sites in `src/`, printed
     * so the claim "there are N class enumerators and they are all guarded" is
     * auditable from the gate's own output rather than from a one-off grep.
     *
     * Sites the structured discovery judged as class enumerators are marked; the
     * rest are directory uses that never derive a class name (`.tl` schemes,
     * migrations, backup payloads, template includes) and are therefore out of
     * scope BY CONSTRUCTION — a loop that never builds a class name cannot be
     * defeated by a weak class guard.
     */
    public function test_inventory_of_every_directory_source_in_src(): void
    {
        $judged = [];
        foreach (self::discover() as $site) {
            $judged[$site['file'].':'.$site['enumerationLine']] = true;
        }

        $rows = [];

        foreach (self::phpFilesIn(self::SRC) as $path) {
            $lines = explode("\n", (string) file_get_contents($path));

            foreach ($lines as $index => $line) {
                $trimmed = trim($line);

                // A docblock or a comment MENTIONING glob( is not a call site;
                // counting one would put a number in the report that nothing can
                // reproduce.
                if (str_starts_with($trimmed, '*') || str_starts_with($trimmed, '//') || str_starts_with($trimmed, '#') || str_starts_with($trimmed, '/*')) {
                    continue;
                }

                foreach (self::DIRECTORY_SOURCES as $needle) {
                    if (! str_contains($line, $needle.'(') && ! str_contains($line, 'new '.$needle.'(')) {
                        continue;
                    }

                    $where = $path.':'.($index + 1);

                    $rows[] = sprintf(
                        '%-58s %-6s %-22s %s',
                        $where,
                        isset($judged[$where]) ? 'CLASS' : 'file',
                        $needle,
                        $trimmed,
                    );

                    break;
                }
            }
        }

        fwrite(STDERR, "\n--- every directory-source call site in src/ (".count($rows)." total) ---\n".implode("\n", $rows)."\n");

        self::assertNotSame([], $rows, 'src/ contains no directory-source call site at all — the inventory scanner has rotted.');
    }

    // ------------------------------------------------------------ judgement

    /**
     * Simulate one enumerator with its OWN guard against its OWN directory, and
     * insist that the classes it admits survive the consumer's operation.
     *
     * @param  array<string, mixed>  $site
     * @return string one report line
     */
    private static function judge(array $site): string
    {
        $where = $site['file'].':'.$site['line'];
        $files = $site['files'];

        if ($files === []) {
            self::fail("{$where}: enumeration pattern resolved to a directory with no files ({$site['dir']}) — refusing to pass a check that measured nothing.");
        }

        $shapes = [];
        foreach ($files as $file) {
            $fqcn = $site['namespace'].basename($file, '.php');

            if (! class_exists($fqcn) && ! trait_exists($fqcn) && ! interface_exists($fqcn) && ! enum_exists($fqcn)) {
                self::fail("{$where}: {$fqcn} does not autoload from {$file} — the enumeration names a class that does not exist.");
            }

            $shapes[$fqcn] = self::shapeOf($fqcn);
        }

        $need = $site['need'];
        $guards = self::guardText($site);
        $kinds = $site['kinds'];

        // Concrete classes in the directory the consumer could never use — the
        // population an existence-only guard would hand straight to `new`.
        $foreignConcrete = [];
        foreach ($shapes as $fqcn => $shape) {
            if ($shape['kind'] === 'class' && ! $shape['isAbstract'] && ! self::satisfies($fqcn, $need)) {
                $foreignConcrete[] = $fqcn;
            }
        }

        // (1) THE GUARD-STRENGTH TRIP-WIRE. An existence-only guard over a directory
        // that holds a concrete class the consumer cannot use is the production
        // incident waiting for the next parked class — today it is a live
        // sentinel (UnmappedEntityPeer), so a reverted guard fails HERE and again
        // at (3) below, on the admitted set.
        self::assertTrue(
            $kinds !== [] || $foreignConcrete === [],
            sprintf(
                "%s: guard %s is EXISTENCE-ONLY over a directory holding %d concrete class(es) the consumer cannot use — %s.\n"
                .'  enumerated dir : %s'."\n"
                .'  consumer needs : %s'."\n"
                .'  fix            : guard the KIND (e.g. is_subclass_of($class, %s::class)), not existence.'."\n"
                .'  why it looked fine: an existence-only guard is FALSE for a trait, so parked traits/concerns are skipped'."\n"
                ."                           — the trait skip is what read as evidence of filtering.\n",
                $where,
                $guards,
                count($foreignConcrete),
                implode(', ', array_slice($foreignConcrete, 0, 8)),
                $site['dir'],
                $need['label'],
                $need['class'] ?? 'Model',
            ),
        );

        // (2) THE SIMULATION. Re-evaluate the guard as written, against the real
        // directory, and collect what it admits.
        $admitted = [];
        foreach ($shapes as $fqcn => $shape) {
            $passesExistence = $site['existence'] === [] || self::passesExistence($fqcn, $site['existence']);
            $passesKind = self::passesKindChecks($fqcn, $kinds);

            if ($passesExistence && $passesKind) {
                $admitted[] = $fqcn;
            }
        }

        // (3) THE TRIP-WIRE ON THE ADMITTED SET. Whatever the guard admits must
        // be usable. This is the line that names a weakened guard by file:line.
        $unusable = array_values(array_filter(
            $admitted,
            static fn (string $fqcn): bool => ! self::satisfies($fqcn, $need),
        ));

        self::assertSame(
            [],
            $unusable,
            sprintf(
                "%s: the guard (%s) admits %d class(es) the consumer cannot use — %s.\n"
                .'  enumerated dir : %s'."\n"
                .'  consumer needs : %s'."\n"
                .'  fix            : guard the KIND, not existence.'."\n",
                $where,
                $guards,
                count($unusable),
                implode(', ', array_slice($unusable, 0, 8)),
                $site['dir'],
                $need['label'],
            ),
        );

        // (4) THE PROOF. Run the consumer's operation over the admitted set.
        self::assertNotSame([], $admitted, "{$where}: the guard admits NOTHING from {$site['dir']} — the enumeration is inert or over-filtered.");

        foreach ($admitted as $fqcn) {
            self::consume($fqcn, $need, $where);
        }

        return sprintf(
            '%-56s guard=%-32s %-15s dir=%-34s files=%3d admitted=%3d rejected=%d',
            $where,
            $guards,
            $kinds === [] ? 'EXISTENCE-ONLY' : 'DISCRIMINATING',
            basename(dirname($site['dir'])).'/'.basename($site['dir']),
            count($files),
            count($admitted),
            count($files) - count($admitted),
        );
    }

    // ------------------------------------------------------- the guard itself

    /**
     * The guard region of a loop body: everything before the first CONSUMER of
     * the class (`new $class`, `$class::…`, `->getTable()`). Comments are
     * stripped first, so an explanatory comment naming a predicate or a fatal
     * cannot be mistaken for the code doing either.
     */
    private static function guardRegion(string $body): string
    {
        $code = self::stripComments($body);

        $cut = null;
        foreach (['new $', '->getTable(', '::query(', '->forceFill(', '->getKey('] as $consumer) {
            $at = strpos($code, $consumer);
            if ($at !== false && ($cut === null || $at < $cut)) {
                $cut = $at;
            }
        }

        return $cut === null ? $code : substr($code, 0, $cut);
    }

    /**
     * Existence predicates the guard uses, and kind predicates with the
     * arguments they test — parsed with `use` aliases resolved, so
     * `is_subclass_of($class, Model::class)` is checked against the real
     * Eloquent base class and not against a literal string.
     *
     * @param  array<string, string>  $imports
     * @return array{existence: list<string>, kinds: list<array{type: string, target: string}>}
     */
    private static function parseGuard(string $region, array $imports, ?string $classVar): array
    {
        $existence = [];
        foreach (self::EXISTENCE_GUARDS as $predicate) {
            if (preg_match('/(?<![\w\\\\>])'.$predicate.'\s*\(\s*\$\w+/', $region) === 1) {
                $existence[] = $predicate;
            }
        }

        $kinds = [];

        // `is_subclass_of($class, Model::class)` — the `::class` suffix is part
        // of how the guard is written, so it is optional in the pattern.
        if (preg_match('/is_subclass_of\s*\(\s*\$(\w+)\s*,\s*([\\\\A-Za-z_][\w\\\\]*)(?:\s*::\s*class)?\s*\)/', $region, $m) === 1 && self::guardsThe($classVar, $m[1])) {
            $kinds[] = ['type' => 'subclass_of', 'target' => self::resolveClassName($m[2], $imports)];
        }

        if (preg_match('/is_a\s*\(\s*\$(\w+)\s*,\s*([\\\\A-Za-z_][\w\\\\]*)(?:\s*::\s*class)?\s*,\s*true\s*\)/', $region, $m) === 1 && self::guardsThe($classVar, $m[1])) {
            $kinds[] = ['type' => 'subclass_of', 'target' => self::resolveClassName($m[2], $imports)];
        }

        // An `instanceof` is a guard on the CLASS only when its operand is the
        // enumerated variable. The same keyword applied to the value the class
        // RETURNS (`$declaration instanceof DataDeclaration`) is a contract on
        // the result, not a filter, and must not be read as one.
        if (preg_match('/instanceof\s+\$?([\\\\A-Za-z_][\w\\\\]*)/', $region, $m) === 1 && str_starts_with(ltrim($m[0], 'i'), 'instanceof $')) {
            $operand = substr($m[0], strlen('instanceof $'));
            if (self::guardsThe($classVar, $operand)) {
                $kinds[] = ['type' => 'instanceof', 'target' => self::resolveClassName($operand, $imports)];
            }
        }

        if (preg_match('/is_callable\s*\(\s*\[\s*\$(\w+)\s*,\s*\'(\w+)\'\s*\]/', $region, $m) === 1 && self::guardsThe($classVar, $m[1])) {
            $kinds[] = ['type' => 'callable', 'target' => $m[2]];
        }

        if (preg_match('/method_exists\s*\(\s*\$(\w+)\s*,\s*\'(\w+)\'/', $region, $m) === 1 && self::guardsThe($classVar, $m[1])) {
            $kinds[] = ['type' => 'method', 'target' => $m[2]];
        }

        return ['existence' => $existence, 'kinds' => $kinds];
    }

    /**
     * A predicate guards the enumerated class only when it is applied to the
     * variable the loop derived from the filename. Anything applied to another
     * variable is a different statement.
     */
    private static function guardsThe(?string $classVar, string $used): bool
    {
        return $classVar !== null && $classVar === $used;
    }

    /** The variable the loop body builds the FQCN into. */
    private static function classVariableIn(string $body): ?string
    {
        $pattern = '/\$([a-zA-Z_]\w*)\s*=\s*["\x27](?:\\\\.|[^\\\\"\x27])*["\x27]\s*\.\s*(?:[a-zA-Z_]\w*\s*\(\s*)?basename/';

        if (preg_match($pattern, $body, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    /**
     * @param  list<string>  $existence
     * @param  list<array{type: string, target: string}>  $kinds
     */
    private static function passesExistence(string $fqcn, array $existence): bool
    {
        // `class_exists()` semantics: TRUE for an ordinary class, FALSE for a
        // trait / interface / enum. That gap is the whole reason an
        // existence-only guard reads as sufficient.
        return ! in_array('class_exists', $existence, true) || class_exists($fqcn);
    }

    /** @param list<array{type: string, target: string}> $kinds */
    private static function passesKindChecks(string $fqcn, array $kinds): bool
    {
        foreach ($kinds as $check) {
            $target = $check['target'];

            $ok = match ($check['type']) {
                'subclass_of' => class_exists($fqcn) && is_subclass_of($fqcn, $target),
                'instanceof' => class_exists($fqcn) && is_a($fqcn, $target, true),
                'callable' => method_exists($fqcn, $target) && is_callable([$fqcn, $target]),
                'method' => method_exists($fqcn, $target),
                default => false,
            };

            if (! $ok) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, mixed> $site */
    private static function guardText(array $site): string
    {
        $parts = array_merge(
            $site['existence'] === [] ? [] : ['exists'],
            array_map(
                static fn (array $k): string => $k['type'].'('.$k['target'].')',
                $site['kinds'],
            ),
        );

        return $parts === [] ? 'NONE' : implode('+', $parts);
    }

    /** @param array<string, string> $imports */
    private static function resolveClassName(string $written, array $imports): string
    {
        $written = ltrim($written, '\\');

        if (str_contains($written, '\\') || in_array($written, ['self', 'static', 'parent'], true)) {
            return $written;
        }

        return $imports[$written] ?? '\\'.$written;
    }

    /**
     * Top-level `use X\Y\Z;` / `use X\Y\Z as Alias;` imports, for alias
     * resolution. Function and const imports are ignored.
     *
     * @return array<string, string>
     */
    private static function importsOf(string $source): array
    {
        $imports = [];

        if (preg_match_all('/^use\s+(?!function\s|const\s)([A-Za-z_][\w\\\\]*)\s*(?:as\s+(\w+))?\s*;/m', $source, $matches, PREG_SET_ORDER) === 0) {
            return $imports;
        }

        foreach ($matches as $match) {
            $alias = isset($match[2]) && $match[2] !== '' ? $match[2] : self::lastSegment($match[1]);
            $imports[$alias] = $match[1];
        }

        return $imports;
    }

    private static function lastSegment(string $fqcn): string
    {
        $at = strrpos($fqcn, '\\');

        return $at === false ? $fqcn : substr($fqcn, $at + 1);
    }

    // ------------------------------------------------------- consumer needs

    /**
     * Execute the loop body's operation on an admitted class: reflection where
     * the contract is a signature, real construction where the consumer really
     * constructs the class — that is the call that fataled.
     *
     * @param  array<string, mixed>  $need
     */
    private static function consume(string $fqcn, array $need, string $where): void
    {
        if ($need['kind'] === 'model') {
            try {
                $model = new $fqcn;
            } catch (Throwable $e) {
                self::fail("{$where}: the guard admits {$fqcn}, but constructing it failed — the consumer fatals here: ".$e::class.': '.$e->getMessage());
            }

            self::assertInstanceOf(Model::class, $model, "{$where}: guard admitted a non-model it cannot use.");
            self::assertIsString($model->getTable(), "{$where}: {$fqcn}->getTable() did not yield a table name.");

            return;
        }

        self::assertTrue(
            method_exists($fqcn, 'define'),
            "{$where}: the guard admits {$fqcn}, which has no define() — the consumer calls {$fqcn}::define() and fatals.",
        );

        $method = new ReflectionMethod($fqcn, 'define');
        self::assertTrue($method->isPublic() && $method->isStatic(), "{$where}: {$fqcn}::define() is not a public static method.");

        $type = $method->getReturnType();
        if ($type instanceof ReflectionNamedType && $need['class'] !== null) {
            self::assertSame(
                ltrim($need['class'], '\\'),
                $type->getName(),
                "{$where}: {$fqcn}::define() declares {$type->getName()}, not {$need['class']}.",
            );
        }
    }

    /**
     * The requirement the loop body places on the classes it admits, read off
     * the operation it performs — never off the guard.
     *
     * @param  array<string, string>  $imports
     * @return array{kind: string, label: string, class: string|null}
     */
    private static function needOf(string $code, array $imports): array
    {
        foreach (['->getTable()', '::query()', '->forceFill(', '->getKey('] as $operation) {
            if (str_contains($code, $operation)) {
                return [
                    'kind' => 'model',
                    'label' => 'an Eloquent model — the loop body calls '.$operation.' on the admitted class',
                    'class' => Model::class,
                ];
            }
        }

        if (str_contains($code, '::define()')) {
            return [
                'kind' => 'declaration',
                'label' => 'a declaration class — the loop body calls ::define() on the admitted class',
                'class' => self::declaredDefineReturn($code, $imports),
            ];
        }

        // No recognised consumer operation: the site consumes the class by a
        // route this gate does not model. Named, never silently accepted.
        self::fail("unmodelled consumer operation in a directory-of-classes enumerator body:\n".trim($code));
    }

    /**
     * The return type the enumerator's own instanceof-refusal names, if any,
     * with `use` aliases resolved so it compares against the FQCN a reflection
     * return type reports.
     *
     * @param  array<string, string>  $imports
     */
    private static function declaredDefineReturn(string $body, array $imports): ?string
    {
        if (preg_match('/instanceof\s+\\\\?([A-Za-z_][A-Za-z0-9_\\\\]*)\b/', $body, $m) === 1) {
            return self::resolveClassName($m[1], $imports);
        }

        return null;
    }

    /** @param array{kind: string, label: string, class: string|null} $need */
    private static function satisfies(string $fqcn, array $need): bool
    {
        if ($need['kind'] === 'model') {
            return is_subclass_of($fqcn, Model::class);
        }

        return method_exists($fqcn, 'define');
    }

    /** @return array{kind: string, isAbstract: bool} */
    private static function shapeOf(string $fqcn): array
    {
        if (trait_exists($fqcn)) {
            return ['kind' => 'trait', 'isAbstract' => false];
        }

        if (interface_exists($fqcn) || enum_exists($fqcn)) {
            return ['kind' => 'interface', 'isAbstract' => true];
        }

        $reflection = new ReflectionClass($fqcn);

        return ['kind' => 'class', 'isAbstract' => $reflection->isAbstract()];
    }

    // ------------------------------------------------------------- discovery

    /**
     * Every `src/` file that enumerates a directory of PHP classes and consumes
     * the classes it names. Nothing here is hard-coded to one path: sites are
     * found by shape, and each site's guard is read out of the source found.
     *
     * @return list<array{file: string, line: int, dir: string, files: list<string>, namespace: string, existence: list<string>, kinds: list<array{type: string, target: string}>, need: array<string, mixed>}>
     */
    private static function discover(): array
    {
        $sites = [];

        foreach (self::phpFilesIn(self::SRC) as $path) {
            $source = (string) file_get_contents($path);
            if (self::mentionsNoDirectorySource($source)) {
                continue;
            }

            $imports = self::importsOf($source);

            foreach (self::foreachHeaders($source) as $header) {
                $enumeration = self::enumerationIn($header, $source);
                if ($enumeration === null) {
                    continue;
                }

                $body = self::stripComments(substr($source, $header['bodyStart'], $header['bodyLength']));

                if (! self::derivesAClassName($body)) {
                    continue; // a file enumerator (templates, migrations, .tl) — out of scope
                }

                if (! self::consumesTheClass($body)) {
                    continue; // names classes, consumes none
                }

                $namespace = self::classPrefixIn($body);
                if ($namespace === null) {
                    self::fail(
                        'could not read the class-name prefix in the enumerator body at '.$path.':'.$header['line']."\n".trim($body),
                    );
                }

                $pattern = self::resolvePath($enumeration['expression'], dirname($path), $source, $enumeration['offset'], 0);
                if ($pattern === null) {
                    self::fail(
                        'could not resolve the directory expression `'.$enumeration['expression'].'` for the enumerator at '
                        .$path.':'.$header['line'].' — a lookup that did not happen is not a lookup that found nothing clean.',
                    );
                }

                $files = glob($pattern) ?: [];
                sort($files);

                $guard = self::parseGuard(self::guardRegion($body), $imports, self::classVariableIn($body));

                $sites[] = [
                    'file' => $path,
                    'line' => $header['line'],
                    'enumerationLine' => substr_count($source, "\n", 0, $enumeration['offset']) + 1,
                    'dir' => $pattern,
                    'files' => $files,
                    'namespace' => $namespace,
                    'existence' => $guard['existence'],
                    'kinds' => $guard['kinds'],
                    'need' => self::needOf($body, $imports),
                ];
            }
        }

        return $sites;
    }

    /** @return list<string> */
    private static function phpFilesIn(string $dir): array
    {
        self::assertTrue(is_dir($dir), 'src directory not found: '.$dir);

        $out = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $out[] = $file->getPathname();
            }
        }

        sort($out);

        return $out;
    }

    private static function mentionsNoDirectorySource(string $source): bool
    {
        foreach (self::DIRECTORY_SOURCES as $needle) {
            if (str_contains($source, $needle)) {
                return false;
            }
        }

        return true;
    }

    /** Does the body actually operate on the class it names? */
    private static function consumesTheClass(string $body): bool
    {
        foreach (['->getTable()', '::query()', '->forceFill(', '->getKey(', '::define()'] as $consumer) {
            if (str_contains($body, $consumer)) {
                return true;
            }
        }

        return false;
    }

    /** Remove comments from a source slice, preserving offsets and newlines. */
    private static function stripComments(string $source): string
    {
        $out = '';
        $length = strlen($source);
        $quote = '';
        $i = 0;

        while ($i < $length) {
            $char = $source[$i];
            $next = $i + 1 < $length ? $source[$i + 1] : '';

            if ($quote !== '') {
                $out .= $char;

                if ($char === '\\' && $next !== '') {
                    $out .= $next;
                    $i += 2;

                    continue;
                }

                if ($char === $quote) {
                    $quote = '';
                }

                $i++;

                continue;
            }

            if ($char === "'" || $char === '"') {
                $quote = $char;
                $out .= $char;
                $i++;

                continue;
            }

            if ($char === '/' && $next === '/') {
                while ($i < $length && $source[$i] !== "\n") {
                    $i++;
                }

                continue;
            }

            if ($char === '#' && ($i === 0 || $source[$i - 1] === "\n")) {
                while ($i < $length && $source[$i] !== "\n") {
                    $i++;
                }

                continue;
            }

            if ($char === '/' && $next === '*') {
                $end = strpos($source, '*/', $i + 2);
                $i = $end === false ? $length : $end + 2;

                continue;
            }

            $out .= $char;
            $i++;
        }

        return $out;
    }

    /**
     * Every `foreach (` in the file: header expression, body byte range, and
     * the header's line number.
     *
     * @return list<array{expression: string, bodyStart: int, bodyLength: int, line: int, offset: int, expressionOffset: int}>
     */
    private static function foreachHeaders(string $source): array
    {
        $out = [];
        $offset = 0;

        while (($open = strpos($source, 'foreach', $offset)) !== false) {
            $offset = $open + 7;

            // Only a real `foreach (` statement, not a word ending in "foreach".
            if ($open > 0 && preg_match('/[A-Za-z0-9_$]/', $source[$open - 1]) === 1) {
                continue;
            }

            $paren = strpos($source, '(', $open);
            if ($paren === false || $paren - $open > 20) {
                continue;
            }

            $close = self::matchPair($source, $paren, '(', ')');
            if ($close === null) {
                continue;
            }

            $brace = strpos($source, '{', $close);
            if ($brace === false) {
                continue;
            }

            $bodyClose = self::matchPair($source, $brace, '{', '}');
            if ($bodyClose === null) {
                continue;
            }

            $out[] = [
                'expression' => substr($source, $paren + 1, $close - $paren - 1),
                'bodyStart' => $brace,
                'bodyLength' => $bodyClose - $brace,
                'line' => substr_count($source, "\n", 0, $open) + 1,
                'offset' => $open,
                'expressionOffset' => $paren + 1,
            ];
        }

        return $out;
    }

    /**
     * The directory expression behind a foreach header, whether the header calls
     * a directory source directly (`glob($dir.'/*.php') ?: []`) or iterates a
     * variable assigned one a few lines above (`$files = glob(...); foreach
     * ($files as $file)`) — the shape both live enumerators use. Returns the
     * expression with the byte offset it was found at, so its own `$var`s can be
     * resolved against the surrounding source.
     *
     * @param  array{expression: string, bodyStart: int, bodyLength: int, line: int, offset: int, expressionOffset: int}  $header
     * @return array{expression: string, offset: int}|null
     */
    private static function enumerationIn(array $header, string $source): ?array
    {
        $direct = self::directorySourceCall($header['expression'], $header['expressionOffset']);
        if ($direct !== null) {
            return $direct;
        }

        if (preg_match('/^\s*\$([a-zA-Z_]\w*)/', $header['expression'], $m) !== 1) {
            return null;
        }

        // Indirect: `$var` assigned from a directory source earlier in the method.
        $windowStart = max(0, $header['offset'] - 4000);
        $window = substr($source, $windowStart, $header['offset'] - $windowStart);

        if (preg_match_all('/\$([a-zA-Z_]\w*)\s*=\s*([^;\n]+)/', $window, $assigns, PREG_OFFSET_CAPTURE) === 0) {
            return null;
        }

        foreach ($assigns[1] as $index => $name) {
            if ($name[0] !== $m[1]) {
                continue;
            }

            $expression = trim($assigns[2][$index][0]);
            $found = self::directorySourceCall($expression, $windowStart + $assigns[2][$index][1]);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * The argument of a directory-source call inside an expression, with the
     * byte offset the expression sits at.
     *
     * @return array{expression: string, offset: int}|null
     */
    private static function directorySourceCall(string $expression, int $baseOffset): ?array
    {
        $expression = trim($expression);

        foreach (self::DIRECTORY_SOURCES as $needle) {
            $at = strpos($expression, $needle.'(');
            if ($at === false) {
                continue;
            }

            $open = $at + strlen($needle);
            $close = self::matchPair($expression, $open, '(', ')');
            if ($close === null) {
                continue;
            }

            return [
                'expression' => substr($expression, $open + 1, $close - $open - 1),
                'offset' => $baseOffset + $at,
            ];
        }

        return null;
    }

    private static function matchPair(string $source, int $open, string $a, string $b): ?int
    {
        $depth = 0;
        $inString = false;
        $quote = '';

        for ($i = $open, $length = strlen($source); $i < $length; $i++) {
            $char = $source[$i];

            if ($inString) {
                if ($char === '\\') {
                    $i++;
                } elseif ($char === $quote) {
                    $inString = false;
                }

                continue;
            }

            if ($char === "'" || $char === '"') {
                $inString = true;
                $quote = $char;

                continue;
            }

            if ($char === $a) {
                $depth++;
            } elseif ($char === $b) {
                $depth--;

                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return null;
    }

    /** Does the body build a class name out of the loop variable? */
    private static function derivesAClassName(string $body): bool
    {
        if (preg_match('/\bbasename\s*\(\s*\$\w+/', $body) !== 1) {
            return false;
        }

        return preg_match('/new\s+\$/', $body) === 1
            || preg_match('/\$[a-zA-Z_]\w*\s*::/', $body) === 1
            || preg_match('/ReflectionClass\s*\(\s*\$/', $body) === 1
            || preg_match('/::[a-zA-Z_]\w*\(\)/', $body) === 1;
    }

    /** The literal namespace prefix the loop prefixes onto `basename($file)`. */
    private static function classPrefixIn(string $body): ?string
    {
        // The quoted literal that feeds `basename(...)` — directly
        // (`'…\\Models\\'.basename($file, '.php')`) or wrapped
        // (`'…\\Declarations\\'.substr(basename($file), 0, -4)`) — captured raw
        // and unescaped with PHP single-quote semantics.
        $pattern = '~(["\x27])((?:\\\\.|[^\\\\"\x27])*)\1\s*\.\s*(?:[a-zA-Z_]\w*\s*\(\s*)?basename~';

        if (preg_match($pattern, $body, $m) !== 1) {
            return null;
        }

        return str_replace(['\\\\', "\\'"], ['\\', "'"], $m[2]);
    }

    // ------------------------------------------------------ path resolution

    /**
     * Resolve a directory expression to an absolute glob pattern by walking
     * `dirname(__DIR__, N)`, `__DIR__`, string literals and same-method `$var`
     * assignments. Returns null rather than a guess when the expression is
     * anything else — the caller turns that into a named failure, so an
     * unmodelled shape is reported instead of skipped.
     */
    private static function resolvePath(string $expression, string $fileDir, string $source, int $offset, int $depth): ?string
    {
        if ($depth > 6) {
            return null;
        }

        $segments = self::pathSegments($expression);
        if ($segments === null) {
            return null;
        }

        $out = '';

        foreach ($segments as $segment) {
            if ($segment['kind'] === 'dirname') {
                $parts = self::splitArgs($segment['args']);
                $inner = self::resolvePath($parts[0] ?? '', $fileDir, $source, $offset, $depth + 1);
                if ($inner === null || $inner === '') {
                    return null;
                }

                $levels = isset($parts[1]) && is_numeric(trim($parts[1])) ? (int) trim($parts[1]) : 1;
                $out .= dirname($inner, $levels);

                continue;
            }

            if ($segment['kind'] === 'dir') {
                $out .= $fileDir;

                continue;
            }

            if ($segment['kind'] === 'literal') {
                $out .= $segment['value'];

                continue;
            }

            // A variable: resolve its own assignment, earlier in the same method.
            $windowStart = max(0, $offset - 4000);
            $window = substr($source, $windowStart, $offset - $windowStart);

            if (preg_match_all('/\$([a-zA-Z_]\w*)\s*=\s*([^;\n]+)/', $window, $assigns, PREG_OFFSET_CAPTURE) === 0) {
                return null;
            }

            $resolved = null;
            foreach ($assigns[1] as $index => $name) {
                if ($name[0] !== $segment['value']) {
                    continue;
                }

                // The most recent assignment before this use wins, so keep
                // walking and hold the last candidate rather than the first.
                $resolved = self::resolvePath(
                    trim($assigns[2][$index][0]),
                    $fileDir,
                    $source,
                    $windowStart + $assigns[2][$index][1],
                    $depth + 1,
                );
            }

            if ($resolved === null) {
                return null;
            }

            $out .= $resolved;
        }

        return $out === '' ? null : $out;
    }

    /**
     * Split a call's argument list on its top-level commas.
     *
     * @return list<string>
     */
    private static function splitArgs(string $args): array
    {
        $out = [];
        $depth = 0;
        $start = 0;

        for ($i = 0, $length = strlen($args); $i < $length; $i++) {
            $char = $args[$i];

            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            } elseif ($char === ',' && $depth === 0) {
                $out[] = trim(substr($args, $start, $i - $start));
                $start = $i + 1;
            }
        }

        $tail = trim(substr($args, $start));
        if ($tail !== '' || $out !== []) {
            $out[] = $tail;
        }

        return $out;
    }

    /**
     * Split a path expression into `dirname(...)`, `__DIR__`, quoted literals and
     * `$vars`, rejecting anything else (so a call the gate cannot model is
     * reported rather than half-resolved).
     *
     * @return list<array{kind: string, value?: string, args?: string}>|null
     */
    private static function pathSegments(string $expression): ?array
    {
        $expression = trim($expression);

        $pattern = '/dirname\s*\(\s*([^()]*)\s*\)|__DIR__|\'([^\']*)\'|"([^"]*)"|\$([a-zA-Z_]\w*)/';

        if (preg_match_all($pattern, $expression, $matches, PREG_OFFSET_CAPTURE) === 0) {
            return null;
        }

        $segments = [];
        $consumed = 0;

        foreach ($matches[0] as $index => $match) {
            $text = $match[0];
            $at = $match[1];

            // Anything but whitespace, concatenation and a trailing boolean
            // operator between segments is an unmodelled expression.
            $between = substr($expression, $consumed, $at - $consumed);
            if (trim($between, " \t\n\r\0\x0B.?") !== '') {
                return null;
            }

            $consumed = $at + strlen($text);

            if (str_starts_with($text, 'dirname')) {
                $segments[] = ['kind' => 'dirname', 'args' => $matches[1][$index][0]];
            } elseif ($text === '__DIR__') {
                $segments[] = ['kind' => 'dir'];
            } elseif (($matches[2][$index][1] ?? -1) !== -1) {
                $segments[] = ['kind' => 'literal', 'value' => $matches[2][$index][0]];
            } elseif (($matches[3][$index][1] ?? -1) !== -1) {
                $segments[] = ['kind' => 'literal', 'value' => $matches[3][$index][0]];
            } else {
                $segments[] = ['kind' => 'var', 'value' => $matches[4][$index][0]];
            }
        }

        $tail = substr($expression, $consumed);
        if (trim($tail, " \t\n\r\0\x0B.?") !== '') {
            return null;
        }

        return $segments;
    }
}
