<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use PHPUnit\Framework\TestCase;
use MeRezaRezaei\Teleframe\Schema\Generator\ModelGenerator;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;

final class ModelGeneratorTest extends TestCase
{
    /** @return array<string,string> */
    private static function generate(): array
    {
        $scheme = TlParser::parseFile(__DIR__ . '/fixtures/mini.tl', 0, strict: true);
        return (new ModelGenerator())->generate($scheme);
    }

    public function test_anchor_model(): void
    {
        $files = self::generate();
        self::assertArrayHasKey('TlUser.php', $files);
        self::assertStringContainsString('final class TlUser extends TlAnchorModel', $files['TlUser.php']);
        self::assertStringContainsString("protected \$table = 'tl_user';", $files['TlUser.php']);
    }

    public function test_ctor_model_casts(): void
    {
        $files = self::generate();
        self::assertArrayHasKey('TlUserUserEmpty.php', $files);
        $model = $files['TlUserUserEmpty.php'];
        self::assertStringContainsString('final class TlUserUserEmpty extends TlInstanceModel', $model);
        self::assertStringContainsString("protected \$table = 'tl_user_user_empty';", $model);
        self::assertStringContainsString("'tl_id' => 'int'", $model);
        self::assertStringContainsString('use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;', $model);
    }

    public function test_vector_child_method_and_model(): void
    {
        $files = self::generate();
        self::assertArrayHasKey('TlMessagesMessagesMessages.php', $files);
        $ctor = $files['TlMessagesMessagesMessages.php'];
        self::assertStringContainsString('public function messages(): HasMany', $ctor);
        self::assertStringContainsString('$this->tlChild(TlMessagesMessagesMessagesMessages::class);', $ctor);
        // child model
        self::assertArrayHasKey('TlMessagesMessagesMessagesMessages.php', $files);
        $child = $files['TlMessagesMessagesMessagesMessages.php'];
        self::assertStringContainsString("protected \$table = 'tl_messages_messages_messages__messages';", $child);
        // child tables carry no timestamps columns (migration shape) — the
        // model must not let Eloquent maintain them
        self::assertStringContainsString('public $timestamps = false;', $child);
    }

    public function test_deterministic(): void
    {
        self::assertSame(self::generate(), self::generate());
    }

    public function test_generated_files_are_valid_php(): void
    {
        foreach (self::generate() as $name => $content) {
            $tmp = tempnam(sys_get_temp_dir(), 'tlmdl') . '.php';
            file_put_contents($tmp, $content);
            exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $out, $code);
            unlink($tmp);
            self::assertSame(0, $code, "php -l failed for {$name}: " . implode("\n", $out));
        }
    }
}
