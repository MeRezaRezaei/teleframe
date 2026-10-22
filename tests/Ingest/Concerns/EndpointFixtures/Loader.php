<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\EndpointFixtures;

use RuntimeException;

/**
 * THE GROUP-FILE LOADER — and the contract it publishes to the sibling lanes
 * writing `Group*.php` into THIS directory.
 *
 * Nine sibling lanes write one group file each, all at the same time, into the
 * same directory. This loader is what they are coding against, so the contract
 * is stated here AND verified here rather than trusted: a sibling that codes to
 * a documented-but-unenforced shape produces fixtures that look present and
 * ingest as nothing.
 *
 * ## FILE NAME -> TYPE -> NAMESPACE
 *
 * - Directory: `tests/Ingest/Concerns/EndpointFixtures/`
 * - Namespace: `MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\EndpointFixtures`
 * - File basename === the trait/class name, so PSR-4 resolves it and
 *   `glob('Group*.php')` finds it.
 *
 * A group file declares EITHER a trait or a class; both are accepted:
 *
 * ```php
 * // the dispatched shape — a trait, payloads() only
 * trait GroupPR
 * {
 *     public static function payloads(): array;  // ctor => decoded payload
 * }
 *
 * // also accepted, because an earlier contract revision published this form
 * final class GroupPR
 * {
 *     public static function payloads(): array;
 * }
 * ```
 *
 * A TRAIT's static methods cannot be called directly — `GroupPR::payloads()` on
 * a trait is a fatal — so the loader declares a thin proxy class that `use`s
 * the trait. That is the only construct PHP offers for a `use` written at
 * runtime, and `phpstan.neon.dist` analyses `src` only, so the engine's static
 * gates are untouched.
 *
 * ## THE CONTRACT THIS LOADER ENFORCES
 *
 * 1. **DISCOVERY.** `glob('Group*.php')`. A group file landing later is picked
 *    up with no edit here. A zero-match result is asserted to be non-zero
 *    upstream in `EveryEndpointHasAFixtureTest`: a loader that matched nothing
 *    would guard nothing.
 * 2. **TOLERANCE.** A missing file is not discovered; a file that is present
 *    but half-written (parse error) or that declares no usable `payloads()`
 *    is RECORDED IN `skipped` and skipped. Sibling lanes land concurrently, so
 *    a transient `ParseError` must not fatal the whole suite — but it must also
 *    not vanish silently, which is why it is reported rather than swallowed.
 * 3. **SHAPE.** Every payload must be an array whose ROOT node carries
 *    `'_' => <its own ctor key>`, and every nested node that declares `_` must
 *    name a BARE constructor. A fixture built from a convenient shape proves
 *    nothing about the shape that arrives; this is the falsifiable core of the
 *    convention `HasNestedUpdateFixtures` establishes.
 * 4. **DUPLICATES.** A constructor provided by two groups is a defect, because
 *    it is also somebody else's. It is recorded per constructor with BOTH group
 *    names rather than silently letting the last writer win, and
 *    `HasEndpointFixtures::endpointFixtures()` raises on it.
 *
 * Provided-but-unclassified is NOT rejected here (that is the completeness
 * gate's job, and it names the offender); owned-but-not-provided is not
 * rejected either (that is a group's honest partial state, and it surfaces as
 * a NAMED missing endpoint rather than a fatal).
 *
 * ## NO CREDENTIALS
 *
 * Every value in these fixtures is synthetic: no session string, no real
 * `access_hash`, no database password.
 */
final class Loader
{
    /** The namespace every group file must declare itself in. */
    public const GROUP_NAMESPACE = 'MeRezaRezaei\\Teleframe\\Tests\\Ingest\\Concerns\\EndpointFixtures';

    /**
     * The group basenames present on disk, sorted for a stable failure order.
     *
     * @return list<string>
     */
    public static function discoverGroups(): array
    {
        $files = glob(__DIR__.'/Group*.php');

        if ($files === false) {
            return [];
        }

        sort($files);

        $groups = [];
        foreach ($files as $file) {
            $base = basename($file, '.php');

            // `Group.php` on its own is not a group: a group is `Group` + letters.
            if ($base === 'Group') {
                continue;
            }

            $groups[] = $base;
        }

        return $groups;
    }

    /**
     * Every group file's payloads, merged, with the defects recorded rather
     * than papered over.
     *
     * @return array{
     *     payloads: array<string, array<string, mixed>>,
     *     owners: array<string, string>,
     *     duplicates: array<string, list<string>>,
     *     skipped: list<array{group: string, reason: string}>,
     *     groups: list<string>,
     * }
     */
    public static function collect(): array
    {
        $payloads = [];
        $owners = [];
        $duplicates = [];
        $skipped = [];
        $groups = self::discoverGroups();

        foreach ($groups as $group) {
            try {
                $provider = self::providerFor($group);
            } catch (RuntimeException $e) {
                $skipped[] = ['group' => $group, 'reason' => $e->getMessage()];

                continue;
            }

            if ($provider === null) {
                $skipped[] = [
                    'group' => $group,
                    'reason' => 'declares neither a trait nor a class of that name, or no payloads() method',
                ];

                continue;
            }

            /** @var array<array-key, mixed> $groupPayloads */
            $groupPayloads = $provider::payloads();

            foreach ($groupPayloads as $ctor => $payload) {
                $ctor = (string) $ctor;

                if (! is_array($payload)) {
                    throw new RuntimeException(sprintf(
                        'group %s: payloads()["%s"] must be an array, got %s. A fixture is a decoded '
                        .'payload; a scalar cannot stand in for one.',
                        $group,
                        $ctor,
                        get_debug_type($payload),
                    ));
                }

                self::assertWireShaped($group, $ctor, $payload);

                if (isset($owners[$ctor])) {
                    $duplicates[$ctor][] = $owners[$ctor];
                    $duplicates[$ctor][] = $group;

                    continue; // the FIRST writer is kept; the collision is the finding
                }

                $owners[$ctor] = $group;
                $payloads[$ctor] = $payload;
            }
        }

        return [
            'payloads' => $payloads,
            'owners' => $owners,
            'duplicates' => $duplicates,
            'skipped' => $skipped,
            'groups' => $groups,
        ];
    }

    /**
     * The class that exposes `payloads()` for a group, or null when the file
     * declares nothing usable.
     *
     * @throws RuntimeException when the file is present but not yet parsable —
     *                          a sibling caught mid-write, not a defect of ours.
     */
    private static function providerFor(string $group): ?string
    {
        $file = __DIR__.'/'.$group.'.php';

        if (! is_file($file)) {
            return null;
        }

        $fqcn = self::GROUP_NAMESPACE.'\\'.$group;

        // Include the path ourselves rather than leaning on the autoloader, so a
        // half-written file arrives as a catchable ParseError instead of a fatal
        // from inside a class_exists() autoload attempt.
        try {
            require_once $file;
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf(
                'group file %s.php could not be parsed (%s: %s). A lane is most likely mid-write; '
                .'this is tolerated and reported, not fatal.',
                $group,
                $e::class,
                $e->getMessage(),
            ));
        }

        // autoload=false: the file is already included, so letting the autoloader
        // include it again could redeclare the type and fatal.
        if (trait_exists($fqcn, false)) {
            return self::proxyFor($fqcn, $group);
        }

        if (class_exists($fqcn, false) && method_exists($fqcn, 'payloads')) {
            return $fqcn;
        }

        return null;
    }

    /**
     * Declare a class that `use`s a group's trait, so its static `payloads()`
     * is actually callable.
     */
    private static function proxyFor(string $traitFqcn, string $group): string
    {
        $proxyName = $group.'Proxy';
        $proxyFqcn = self::GROUP_NAMESPACE.'\\Proxy\\'.$proxyName;

        if (! class_exists($proxyFqcn, false)) {
            // phpstan analyses `src` only (phpstan.neon.dist `paths: [src]`), so a
            // runtime `use` here cannot weaken the engine's static gates.
            eval(sprintf(
                'namespace %s\\Proxy; final class %s { use \\%s; }',
                self::GROUP_NAMESPACE,
                $proxyName,
                $traitFqcn,
            ));
        }

        return $proxyFqcn;
    }

    /**
     * THE SHAPE CONTRACT, on the two properties that are falsifiable without a
     * TL codec: the root names itself, and no constructor node wears a `#hex`
     * id. What this does NOT prove is recorded honestly in the gate test's
     * docblock rather than implied.
     *
     * @param  array<string, mixed>  $payload
     */
    private static function assertWireShaped(string $group, string $ctor, array $payload): void
    {
        $root = $payload['_'] ?? null;

        if (! is_string($root) || $root !== $ctor) {
            throw new RuntimeException(sprintf(
                'group %s: the fixture keyed "%s" must carry \'_\' => \'%s\' on its root node, because '
                .'that is how the wire decodes it. Got %s. A fixture shaped for convenience rather than '
                .'for the wire proves nothing about the payload that arrives.',
                $group,
                $ctor,
                $ctor,
                var_export($root, true),
            ));
        }

        foreach (self::constructorNodes($payload) as [$path, $name]) {
            if (str_contains($name, '#')) {
                throw new RuntimeException(sprintf(
                    'group %s: fixture "%s" declares the constructor node at %s as "%s". The decoded '
                    .'form carries the BARE constructor name; a "#hex" id is the TL declaration, not '
                    .'the decoded payload.',
                    $group,
                    $ctor,
                    $path,
                    $name,
                ));
            }
        }
    }

    /**
     * Every constructor node in a payload, with a dotted path to each.
     *
     * Structural, not a name check: `message` arrives NESTED inside
     * `updateNewMessage`, which is the case that must not be missed.
     *
     * @param  array<array-key, mixed>  $node
     * @return list<array{0: string, 1: string}>
     */
    private static function constructorNodes(array $node, string $path = ''): array
    {
        $found = [];

        if (isset($node['_']) && is_string($node['_'])) {
            $found[] = [$path === '' ? '(root)' : $path, $node['_']];
        }

        foreach ($node as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            $child = $path === '' ? (string) $key : $path.'.'.$key;

            foreach (self::constructorNodes($value, $child) as $hit) {
                $found[] = $hit;
            }
        }

        return $found;
    }
}
