<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel;

use Illuminate\Support\ServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\AppServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\NotifyServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\PassportServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\StoreBackupServiceProvider;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use Spatie\LaravelData\LaravelDataServiceProvider;

/**
 * The config publish SURFACE, asserted without a network and without building a
 * Laravel host.
 *
 * bin/prove-host-install.php proves it for real: it builds a throwaway host and
 * publishes the umbrella tag, asserting every config file lands. That needs the
 * network, so it cannot run on every commit. These are the same invariants in
 * fast form, read from the SAME place the gate reads them — the booted
 * ServiceProvider registry — so the two cannot disagree about what is published.
 *
 * The failure this exists to prevent: a host publishes the one tag it can find
 * in the docs and silently receives one config file out of five. Nothing errors.
 * The host then reads package defaults for four surfaces it never chose.
 */
final class ConfigPublishSurfaceTest extends TestCase
{
    /** The tag a host reaches for first. One command must publish everything. */
    private const UMBRELLA_TAG = 'teleframe-config';

    /**
     * The package's config directory is the source of truth for WHAT must be
     * published. This is deliberately derived from disk, not hardcoded: a new
     * config file that nobody publishes is exactly the regression.
     */
    private function configFilesOnDisk(): array
    {
        $files = glob(dirname(__DIR__, 3).'/src/Laravel/config/*.php') ?: [];
        self::assertNotSame([], $files, 'src/Laravel/config/ must contain config files for this test to mean anything');

        return array_map(static fn (string $p): string => basename($p), $files);
    }

    /** @return list<string> the published SOURCE basenames under one tag. */
    private function sourcesUnderTag(string $tag): array
    {
        $paths = ServiceProvider::pathsToPublish(null, $tag);
        self::assertIsArray($paths, 'pathsToPublish must return an array for tag '.$tag);
        self::assertNotSame([], $paths, 'no paths are published under the tag '.$tag);

        return array_values(array_map(
            static fn (string $from): string => basename($from),
            array_keys($paths),
        ));
    }

    /** @return list<string> every published source basename, across ALL tags. */
    private function allPublishedSources(): array
    {
        $sources = [];
        foreach (ServiceProvider::publishableGroups() as $group) {
            foreach ($this->sourcesUnderTagSafe((string) $group) as $source) {
                $sources[$source] = true;
            }
        }

        return array_keys($sources);
    }

    /**
     * Like sourcesUnderTag() but tolerant: used when sweeping EVERY group, where
     * a group legitimately holding no config file is not itself a finding.
     *
     * @return list<string>
     */
    private function sourcesUnderTagSafe(string $tag): array
    {
        $paths = ServiceProvider::pathsToPublish(null, $tag);

        return is_array($paths)
            ? array_values(array_map(static fn (string $from): string => basename($from), array_keys($paths)))
            : [];
    }

    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        // ALL the providers that publish config. The umbrella tag is registered
        // by one of them, but "no config file is unreferenced by ANY tag" is a
        // claim about the package as a whole, so the whole has to be booted.
        return [
            LaravelDataServiceProvider::class,
            TeleframeServiceProvider::class,
            NotifyServiceProvider::class,
            StoreBackupServiceProvider::class,
            AppServiceProvider::class,
            PassportServiceProvider::class,
        ];
    }

    /**
     * THE ASSERTION. Publishing the umbrella tag must yield every config file the
     * package ships.
     *
     * Asserted as a set comparison in BOTH directions: not "the umbrella covers
     * what I remembered to list" but "the umbrella and the directory agree".
     */
    public function test_the_umbrella_tag_publishes_every_config_file(): void
    {
        $onDisk = $this->configFilesOnDisk();
        $published = $this->sourcesUnderTag(self::UMBRELLA_TAG);

        $missing = array_values(array_diff($onDisk, $published));
        self::assertSame(
            [],
            $missing,
            'the umbrella tag "'.self::UMBRELLA_TAG.'" does not publish: '.implode(', ', $missing)
                .' — a host publishing only this tag would silently get defaults for them',
        );

        $extra = array_values(array_diff($published, $onDisk));
        self::assertSame(
            [],
            $extra,
            'the umbrella tag publishes files that are not in src/Laravel/config/: '.implode(', ', $extra),
        );
    }

    /**
     * No config file may be unreachable by ANY tag. The umbrella test above would
     * pass if a provider published a file the umbrella also lists, while the
     * per-file tag that owns it had silently rotted away; this is the direction
     * that catches a provider losing its own publishes() call.
     */
    public function test_no_config_file_is_unreferenced_by_any_publish_tag(): void
    {
        $orphans = array_values(array_diff($this->configFilesOnDisk(), $this->allPublishedSources()));

        self::assertSame(
            [],
            $orphans,
            'config file(s) that NO publish tag publishes: '.implode(', ', $orphans)
                .' — a host cannot override a file the package never offers to publish',
        );
    }

    /**
     * The per-file tags are part of the public onboarding surface and were there
     * before the umbrella. The umbrella must be ADDITIVE, not a replacement:
     * adding one tag must not break a host that publishes file by file.
     */
    public function test_the_per_file_config_tags_still_publish_their_own_file(): void
    {
        $expected = [
            'teleframe-notify-config' => 'notify.php',
            'teleframe-store-backup-config' => 'store-backup.php',
            'teleframe-app-config' => 'app.php',
            'teleframe-passport-config' => 'passport.php',
        ];

        foreach ($expected as $tag => $file) {
            self::assertContains(
                $file,
                $this->sourcesUnderTagSafe($tag),
                'the per-file tag '.$tag.' must keep publishing '.$file.' — the umbrella must be additive, not a replacement',
            );
        }
    }

    /**
     * The umbrella must not have swallowed the non-config tags. Publishing the
     * dashboard view and the miniapp stub are separate onboarding steps, and
     * folding them into "config" would publish a blade view a host never asked
     * for under a tag whose name promises config only.
     */
    public function test_the_umbrella_tag_contains_only_config_files(): void
    {
        $published = $this->sourcesUnderTag(self::UMBRELLA_TAG);
        $nonConfig = array_values(array_filter(
            $published,
            static fn (string $p): bool => ! str_ends_with($p, '.php'),
        ));

        self::assertSame(
            [],
            $nonConfig,
            'the umbrella config tag must publish config only, but also publishes: '.implode(', ', $nonConfig),
        );
    }
}
