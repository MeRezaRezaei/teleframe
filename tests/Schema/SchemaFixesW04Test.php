<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use MeRezaRezaei\Teleframe\Schema\Generator\SchemaRegenerator;
use PHPUnit\Framework\TestCase;

/**
 * W-04 regression: `regenerate --ship` must NEVER silently delete a
 * hand-authored migration outside the explicit allow-list. Safe default:
 * purge only files whose basename matches the allow-list (nothing is purged
 * by default — matching files are preserved, unmatched ones are warned and
 * left in place); `--prune` sweeps only stale GENERATED-banner regenerable
 * artifacts, never hand-authored files.
 */
final class SchemaFixesW04Test extends TestCase
{
    private const PACKAGE_ROOT = __DIR__.'/../..';

    private const SOURCES = self::PACKAGE_ROOT.'/schema/sources';

    private const HAND_AUTHORED = '2026_09_05_000000_create_fake_widgets_table.php';

    private function shipDir(): string
    {
        $dir = sys_get_temp_dir().'/w04-'.uniqid();
        mkdir($dir.'/src/Laravel/Migrations', 0777, true);

        return $dir;
    }

    public function test_hand_authored_migration_survives_default_ship(): void
    {
        $out = $this->shipDir();
        file_put_contents($out.'/src/Laravel/Migrations/'.self::HAND_AUTHORED, '<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void {}
    public function down(): void {}
};
');

        $result = (new SchemaRegenerator)
            ->shipNamespaces(['messages'])
            ->regenerate(self::SOURCES, $out);

        self::assertFileExists(
            $out.'/src/Laravel/Migrations/'.self::HAND_AUTHORED,
            'W-04: hand-authored migration must survive regenerate --ship with default semantics',
        );
        self::assertContains(self::HAND_AUTHORED, $result['ship']['left'], 'surviving file is reported as left in place');
        self::assertSame([self::HAND_AUTHORED], $result['ship']['left']);
    }

    public function test_hand_authored_migration_survives_prune_opt_in(): void
    {
        $out = $this->shipDir();
        file_put_contents($out.'/src/Laravel/Migrations/'.self::HAND_AUTHORED, '<?php // hand-authored, no banner');

        $result = (new SchemaRegenerator)
            ->shipNamespaces(['messages'])
            ->prune(true)
            ->regenerate(self::SOURCES, $out);

        self::assertFileExists($out.'/src/Laravel/Migrations/'.self::HAND_AUTHORED, '--prune must never touch a hand-authored file');
        self::assertContains(self::HAND_AUTHORED, $result['ship']['left']);
    }

    public function test_bare_stale_migration_is_left_not_deleted(): void
    {
        $out = $this->shipDir();
        $stale = '2020_01_01_000000_create_obsolete_table.php';
        file_put_contents($out.'/src/Laravel/Migrations/'.$stale, '<?php // stale, hand-authored');

        $result = (new SchemaRegenerator)
            ->shipNamespaces(['messages'])
            ->regenerate(self::SOURCES, $out);

        self::assertFileExists($out.'/src/Laravel/Migrations/'.$stale, 'unmatched stale file is left, never deleted');
        self::assertContains($stale, $result['ship']['left']);
    }

    public function test_prune_only_sweeps_generated_banner_artifacts(): void
    {
        $out = $this->shipDir();
        $stale = '2020_01_01_000000_create_obsolete_table.php';
        file_put_contents($out.'/src/Laravel/Migrations/'.$stale, '<?php // GENERATED — do not edit; run artisan teleframe:regenerate');

        $result = (new SchemaRegenerator)
            ->shipNamespaces(['messages'])
            ->prune(true)
            ->regenerate(self::SOURCES, $out);

        self::assertFileDoesNotExist($out.'/src/Laravel/Migrations/'.$stale, '--prune sweeps a stale GENERATED artifact');
        self::assertNotContains($stale, $result['ship']['left']);
    }
}
