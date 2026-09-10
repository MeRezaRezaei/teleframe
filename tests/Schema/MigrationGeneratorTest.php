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
        // One file per TL type, ksort order (Message < MsgsStateInfo < User < UserStatus < messages.Messages).
        self::assertArrayHasKey('2026_08_28_000001_create_tl_message_tables.php', $files);
        self::assertArrayHasKey('2026_08_28_000002_create_tl_msgs_state_info_tables.php', $files);
        self::assertArrayHasKey('2026_08_28_000003_create_tl_user_tables.php', $files);
        self::assertArrayHasKey('2026_08_28_000004_create_tl_user_status_tables.php', $files);
        self::assertArrayHasKey('2026_08_28_000005_create_tl_messages_messages_tables.php', $files);
        self::assertArrayHasKey('2026_08_28_900005_create_tl_route_tables.php', $files);
        self::assertArrayHasKey('2026_08_28_999901_add_tl_foreign_keys.php', $files);
    }

    public function test_global_id_table_shape(): void
    {
        $files = self::generate();
        $user = $files['2026_08_28_000003_create_tl_user_tables.php'];
        self::assertStringContainsString("Schema::create('tl_user_user_empty', function (Blueprint \$table) {", $user);
        self::assertStringContainsString("\$table->bigInteger('id')->primary();", $user);
        self::assertStringContainsString("\$table->bigInteger('constructor_id');", $user);
        self::assertStringContainsString("\$table->string('constructor_name', 96);", $user);
        self::assertStringContainsString("\$table->bigInteger('account_id');", $user);
        self::assertStringNotContainsString("uuid", $user);
    }

    public function test_scoped_id_table_shape(): void
    {
        $files = self::generate();
        // Message has id:int -> scoped, needs composite unique.
        $msgs = $files['2026_08_28_000001_create_tl_message_tables.php'];
        self::assertStringContainsString("Schema::create('tl_message_message'", $msgs);
        self::assertStringContainsString("\$table->bigIncrements('id');", $msgs);
        self::assertStringContainsString("\$table->unique(", $msgs); // composite unique for scoped
        self::assertStringNotContainsString("uuid", $msgs);
    }

    public function test_child_table_for_unconditional_vector(): void
    {
        $files = self::generate();
        $messages = $files['2026_08_28_000005_create_tl_messages_messages_tables.php'];
        self::assertStringContainsString("Schema::create('tl_messages_messages_messages__messages', function (Blueprint \$table) {", $messages);
        self::assertStringContainsString("\$table->bigIncrements('id');", $messages);
        self::assertStringContainsString("\$table->bigInteger('parent_id')", $messages);
        self::assertStringContainsString("\$table->bigInteger('idx');", $messages);
        self::assertStringContainsString("\$table->bigInteger('value_id')->nullable();", $messages);
        self::assertStringContainsString("\$table->unique(['parent_id', 'idx']", $messages);
        self::assertStringNotContainsString("uuid", $messages);
    }

    public function test_route_table(): void
    {
        $files = self::generate();
        $routes = $files['2026_08_28_900005_create_tl_route_tables.php'];
        self::assertStringContainsString("Schema::create('tl_route_help_get_config', function (Blueprint \$table) {", $routes);
        self::assertStringContainsString("\$table->string('route_id', 36)->unique();", $routes);
    }

    public function test_deferred_fk_migration(): void
    {
        $files = self::generate();
        $fks = $files['2026_08_28_999901_add_tl_foreign_keys.php'];
        self::assertStringContainsString(
            'ALTER TABLE "tl_messages_messages_messages__messages" ADD CONSTRAINT tl_messages_messages_messages__messages_value_id_foreign',
            $fks,
        );
        self::assertStringContainsString('FOREIGN KEY (value_id) REFERENCES "tl_message_message" (id) DEFERRABLE INITIALLY DEFERRED', $fks);
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
        $message = array_values($files)[0]; // first file contains message table
        self::assertStringContainsString("\$table->bigInteger('from_id')->nullable();", $message);
        self::assertStringContainsString("\$table->bigInteger('peer_id');", $message);
        self::assertStringNotContainsString("uuid", $message);
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

    public function test_message_table_upholds_account_scoped_uniqueness(): void
    {
        $gen = new MigrationGenerator();
        $scheme = TlParser::parseString(
            "---types---\n"
            . "message#00000001 id:int peer_id:Peer = Message;\n",
        );
        $files = $gen->generate($scheme);
        $migration = array_values($files)[0];
        self::assertStringContainsString("\$table->unique(", $migration); // scoped unique
    }
}
