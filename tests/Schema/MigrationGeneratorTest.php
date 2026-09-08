<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use PHPUnit\Framework\TestCase;
use MeRezaRezaei\Teleframe\Schema\Generator\MigrationGenerator;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;

final class MigrationGeneratorTest extends TestCase
{
    /** @return array<string,string> */
    private static function generate(): array
    {
        $scheme = TlParser::parseFile(__DIR__ . '/fixtures/mini.tl', 0, strict: true);
        return (new MigrationGenerator())->generate($scheme);
    }

    public function test_file_layout(): void
    {
        $files = self::generate();
        // ksort byte order: MsgsStateInfo < User < UserStatus < messages.Messages.
        // Config has no constructors in the fixture (method-return-only) -> no tables.
        self::assertArrayHasKey('2026_08_28_000001_create_tl_msgs_state_info_table.php', $files);
        self::assertArrayHasKey('2026_08_28_000002_create_tl_user_table.php', $files);
        self::assertArrayHasKey('2026_08_28_000003_create_tl_user_status_table.php', $files);
        self::assertArrayHasKey('2026_08_28_000004_create_tl_messages_messages_table.php', $files);
        self::assertArrayHasKey('2026_08_28_900004_create_tl_route_tables.php', $files);
        // Cross-type FKs: bucketed files (FK_BUCKET_SIZE per file) so each
        // migration transaction stays inside stock PG's lock budget.
        self::assertArrayHasKey('2026_08_28_999901_add_tl_foreign_keys.php', $files);
    }

    public function test_anchor_shape(): void
    {
        $files = self::generate();
        $user = $files['2026_08_28_000002_create_tl_user_table.php'];
        self::assertStringContainsString("Schema::create('tl_user', function (Blueprint \$table) {", $user);
        self::assertStringContainsString("\$table->uuid('id')->primary();", $user);
        self::assertStringContainsString("\$table->bigInteger('constructor_id');", $user);
        self::assertStringContainsString("\$table->string('constructor_name', 96);", $user);
        self::assertStringContainsString("\$table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)", $user);
        self::assertStringContainsString("\$table->index('account_id');", $user);
    }

    public function test_instance_table_shape(): void
    {
        $files = self::generate();
        $user = $files['2026_08_28_000002_create_tl_user_table.php'];
        self::assertStringContainsString("Schema::create('tl_user_user_empty', function (Blueprint \$table) {", $user);
        self::assertStringContainsString("\$table->foreignUuid('id')->primary()->constrained('tl_user')->cascadeOnDelete();", $user);
        self::assertStringContainsString("\$table->bigInteger('tl_id');", $user); // id:long unconditional
    }

    public function test_child_table_for_unconditional_vector(): void
    {
        $files = self::generate();
        $messages = $files['2026_08_28_000004_create_tl_messages_messages_table.php'];
        self::assertStringContainsString("Schema::create('tl_messages_messages_messages__messages', function (Blueprint \$table) {", $messages);
        self::assertStringContainsString("\$table->foreignUuid('parent_id')->constrained('tl_messages_messages_messages')->cascadeOnDelete();", $messages);
        self::assertStringContainsString("\$table->bigInteger('idx');", $messages);
        self::assertStringContainsString("\$table->uuid('value_id')->nullable();", $messages);
        self::assertStringContainsString("\$table->unique(['parent_id', 'idx'], 'ux_", $messages);
    }

    public function test_route_table(): void
    {
        $files = self::generate();
        $routes = $files['2026_08_28_900004_create_tl_route_tables.php'];
        self::assertStringContainsString("Schema::create('tl_route_help_get_config', function (Blueprint \$table) {", $routes);
        self::assertStringContainsString("\$table->uuid('route_id')->unique();", $routes);
    }

    public function test_deferred_fk_migration(): void
    {
        $files = self::generate();
        $fks = $files['2026_08_28_999901_add_tl_foreign_keys.php'];
        self::assertStringContainsString(
            'ALTER TABLE "tl_messages_messages_messages__messages" ADD CONSTRAINT tl_messages_messages_messages__messages_value_id_foreign',
            $fks,
        );
        self::assertStringContainsString('FOREIGN KEY (value_id) REFERENCES "tl_message" (id) DEFERRABLE INITIALLY DEFERRED', $fks);
    }

    public function test_fk_files_are_bucketed_within_lock_budget(): void
    {
        $files = self::generate();
        $fkFiles = array_filter(array_keys($files), static fn (string $n): bool => str_contains($n, 'add_tl_foreign_keys'));
        self::assertNotSame([], $fkFiles);
        foreach ($fkFiles as $name) {
            self::assertLessThanOrEqual(
                \MeRezaRezaei\Teleframe\Schema\Generator\MigrationGenerator::FK_BUCKET_SIZE,
                substr_count($files[$name], 'ADD CONSTRAINT'),
                "{$name} must hold at most FK_BUCKET_SIZE ALTERs (PG lock budget)",
            );
        }
    }

    public function test_quote_doubles_embedded_double_quotes(): void
    {
        // SQL-standard identifier doubling: schema names carry no quotes
        // today, but quote() must stay correct if one ever does.
        $quote = new \ReflectionMethod(MigrationGenerator::class, 'quote');
        self::assertSame('"tl_user"', $quote->invoke(null, 'tl_user'));
        self::assertSame('"tl_we""ird"', $quote->invoke(null, 'tl_we"ird'));
    }

    public function test_deterministic(): void
    {
        self::assertSame(self::generate(), self::generate());
    }

    public function test_generated_files_are_valid_php(): void
    {
        foreach (self::generate() as $name => $content) {
            $tmp = tempnam(sys_get_temp_dir(), 'tlmig') . '.php';
            file_put_contents($tmp, $content);
            exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $out, $code);
            unlink($tmp);
            self::assertSame(0, $code, "php -l failed for {$name}: " . implode("\n", $out));
        }
    }
}
