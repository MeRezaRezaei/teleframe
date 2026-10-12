<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use PHPUnit\Framework\TestCase;
use MeRezaRezaei\Teleframe\Schema\Generator\DtoGenerator;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;

final class DtoGeneratorTest extends TestCase
{
    /** @return array<string,string> */
    private static function generate(): array
    {
        $scheme = TlParser::parseFile(__DIR__ . '/fixtures/mini.tl', 0, strict: true);
        return (new DtoGenerator())->generate($scheme);
    }

    public function test_abstract_base_dispatch(): void
    {
        $files = self::generate();
        self::assertArrayHasKey('Types/TlUserAbstractData.php', $files);
        $base = $files['Types/TlUserAbstractData.php'];
        self::assertStringContainsString('abstract class TlUserAbstractData extends Data', $base);
        self::assertStringContainsString("'userEmpty' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\UserEmptyData::class,", $base);
        self::assertStringContainsString('public static function hydrate(array $payload): static', $base);
    }

    public function test_ctor_dto_properties(): void
    {
        $files = self::generate();
        self::assertArrayHasKey('Types/UserEmptyData.php', $files);
        $dto = $files['Types/UserEmptyData.php'];
        self::assertStringContainsString('final class UserEmptyData extends TlUserAbstractData', $dto);
        self::assertStringContainsString('public int $id,', $dto);
    }

    public function test_bytes_docblock(): void
    {
        $files = self::generate();
        $dto = $files['Types/MsgsStateInfoData.php'];
        self::assertStringContainsString('final class MsgsStateInfoData extends TlMsgsStateInfoAbstractData', $dto);
        self::assertStringContainsString('public string $info,', $dto);
    }

    public function test_method_request_dto(): void
    {
        $files = self::generate();
        self::assertArrayHasKey('Methods/TlHelpGetConfigData.php', $files);
        $m = $files['Methods/TlHelpGetConfigData.php'];
        self::assertStringContainsString("public const METHOD = 'help.getConfig';", $m);
        self::assertStringContainsString('public static function method(): string', $m);
    }

    public function test_deterministic(): void
    {
        self::assertSame(self::generate(), self::generate());
    }

    public function test_generated_files_are_valid_php(): void
    {
        foreach (self::generate() as $name => $content) {
            $tmp = tempnam(sys_get_temp_dir(), 'tldto') . '.php';
            file_put_contents($tmp, $content);
            exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $out, $code);
            unlink($tmp);
            self::assertSame(0, $code, "php -l failed for {$name}: " . implode("\n", $out));
        }
    }
}
