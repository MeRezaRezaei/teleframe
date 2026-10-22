<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use PHPUnit\Framework\TestCase;
use MeRezaRezaei\Teleframe\Schema\Generator\FactoryGenerator;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;

final class FactoryGeneratorTest extends TestCase
{
    /** @return array<string,string> */
    private static function generate(): array
    {
        $scheme = TlParser::parseFile(__DIR__ . '/fixtures/mini.tl', 0, strict: true);
        return (new FactoryGenerator())->generate($scheme);
    }

    public function test_factory_for_domain_model(): void
    {
        $files = self::generate();
        self::assertArrayHasKey('TlUserFactory.php', $files);
        $f = $files['TlUserFactory.php'];
        self::assertStringContainsString('final class TlUserFactory extends Factory', $f);
        self::assertStringContainsString("protected \$model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser::class;", $f);
        self::assertStringContainsString("'tl_id' => 1001,", $f);
    }

    public function test_message_factory_exists(): void
    {
        $files = self::generate();
        self::assertArrayHasKey('TlMessageFactory.php', $files);
        $f = $files['TlMessageFactory.php'];
        self::assertStringContainsString('final class TlMessageFactory extends Factory', $f);
        self::assertStringContainsString("protected \$model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessage::class;", $f);
    }

    public function test_no_per_constructor_factories(): void
    {
        $files = self::generate();
        self::assertArrayNotHasKey('TlUserUserEmptyFactory.php', $files);
        self::assertArrayNotHasKey('TlMessageMessageFactory.php', $files);
    }

    public function test_deterministic(): void
    {
        self::assertSame(self::generate(), self::generate());
    }

    public function test_generated_files_are_valid_php(): void
    {
        foreach (self::generate() as $name => $content) {
            $tmp = tempnam(sys_get_temp_dir(), 'tlfac') . '.php';
            file_put_contents($tmp, $content);
            exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $out, $code);
            unlink($tmp);
            self::assertSame(0, $code, "php -l failed for {$name}: " . implode("\n", $out));
        }
    }
}
