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
        // Domain tables use tf_ prefix with sequential numbering
        self::assertArrayHasKey('2026_08_28_000001_create_tf_users_table.php', $files);
        self::assertArrayHasKey('2026_08_28_000004_create_tf_messages_table.php', $files);
        self::assertArrayHasKey('2026_08_28_000013_create_tf_routes_table.php', $files);
    }

    public function test_global_id_table_shape(): void
    {
        $files = self::generate();
        $user = $files['2026_08_28_000001_create_tf_users_table.php'];
        self::assertStringContainsString("Schema::create('tf_users', function (Blueprint \$table) {", $user);
        self::assertStringContainsString("\$table->bigInteger('id');", $user);
        self::assertStringContainsString("\$table->bigInteger('constructor_id');", $user);
        self::assertStringContainsString("\$table->bigInteger('account_id');", $user);
        self::assertStringNotContainsString("uuid", $user);
    }

    public function test_scoped_id_table_shape(): void
    {
        $files = self::generate();
        // Message has composite scope: (peer_id, message_id, account_id)
        $msgs = $files['2026_08_28_000004_create_tf_messages_table.php'];
        self::assertStringContainsString("Schema::create('tf_messages'", $msgs);
        self::assertStringContainsString("\$table->bigInteger('id');", $msgs);
        self::assertStringContainsString("\$table->unique([", $msgs);
        self::assertStringNotContainsString("uuid", $msgs);
    }

    public function test_route_table(): void
    {
        $files = self::generate();
        $routes = $files['2026_08_28_000013_create_tf_routes_table.php'];
        self::assertStringContainsString("Schema::create('tl_route_help_get_config', function (Blueprint \$table) {", $routes);
        self::assertStringContainsString("\$table->string('route_id', 36)->unique();", $routes);
    }

    public function test_fk_files_are_bucketed_within_lock_budget(): void
    {
        $files = self::generate();
        $fkFiles = array_filter(array_keys($files), static fn (string $n): bool => str_contains($n, 'foreign_keys'));
        // mini.tl has no cross-type FK targets, so FK files may be empty
        if ($fkFiles === []) {
            self::markTestSkipped('No FK migration files in mini.tl fixture');
        }
        foreach ($fkFiles as $name) {
            self::assertLessThanOrEqual(
                MigrationGenerator::FK_BUCKET_SIZE,
                substr_count($files[$name], 'ADD CONSTRAINT'),
                "{$name} must hold at most FK_BUCKET_SIZE ALTERs (PG lock budget)",
            );
        }
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

    public function test_peer_ref_columns_are_bigInteger_canonical_long(): void
    {
        $gen = new MigrationGenerator();
        $scheme = TlParser::parseString(
            "---types---\n"
            . "peerUser#00000001 user_id:long = Peer;\n"
            . "message#00000002 id:int from_id:flags.8?Peer peer_id:Peer media:flags.9?MessageMedia = Message;\n"
            . "mediaEmpty#00000003 = MessageMedia;\n",
        );
        $files = $gen->generate($scheme);
        // Domain tables: tf_messages should be in the output
        $found = false;
        foreach ($files as $name => $content) {
            if (str_contains($content, "Schema::create('tf_messages'")) {
                self::assertStringContainsString("\$table->bigInteger('from_id')->nullable();", $content);
                self::assertStringContainsString("\$table->bigInteger('peer_id');", $content);
                self::assertStringNotContainsString("uuid", $content);
                $found = true;
            }
        }
        self::assertTrue($found, 'Expected to find tf_messages migration');
    }

    public function test_every_generated_table_has_account_id_column_and_index(): void
    {
        $gen = new MigrationGenerator();
        $files = $gen->generate(TlParser::parseString(
            "---types---\n"
            . "user#00000001 id:long = User;\n"
            . "message#00000002 id:int text:string entities:flags.0?Vector<string> = Message;\n",
        ));
        foreach ($files as $name => $content) {
            if (str_contains($name, 'route') || str_contains($name, 'foreign_keys')) {
                continue;
            }
            self::assertStringContainsString("\$table->bigInteger('account_id');", $content, "{$name} missing account_id column");
            self::assertStringContainsString("\$table->index('account_id', 'ix_", $content, "{$name} missing account_id index");
        }
    }

    public function test_message_table_scoped_id_no_db_unique(): void
    {
        $gen = new MigrationGenerator();
        $scheme = TlParser::parseString(
            "---types---\n"
            . "message#00000001 id:int peer_id:Peer = Message;\n",
        );
        $files = $gen->generate($scheme);
        // Find the tf_messages migration
        foreach ($files as $name => $content) {
            if (str_contains($content, "Schema::create('tf_messages'")) {
                // Domain messages: uses composite unique on (peer_id, message_id, account_id)
                self::assertStringContainsString("\$table->unique([", $content);
                return;
            }
        }
        self::fail('Expected to find tf_messages migration');
    }
}
