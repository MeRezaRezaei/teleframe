<?php

declare(strict_types=1);

namespace MeRezaRezaei\TeleprotoSchema\Tests;

use MeRezaRezaei\TeleprotoSchema\SchemaArtifacts;
use PHPUnit\Framework\TestCase;

final class SchemaArtifactsTest extends TestCase
{
    public function testPathResolvesInsideThePackageAndFileExists(): void
    {
        foreach (['methods-mtproto.json', 'methods-botapi.json'] as $file) {
            $path = SchemaArtifacts::path($file);
            self::assertStringEndsWith('schema/' . $file, str_replace('\\', '/', $path));
            self::assertFileExists($path);
        }
    }

    public function testSourceResolvesSourcesFile(): void
    {
        $path = SchemaArtifacts::source('api_full.tl');
        self::assertStringEndsWith('schema/sources/api_full.tl', str_replace('\\', '/', $path));
        self::assertFileExists($path);
    }

    public function testPathRejectsTraversal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SchemaArtifacts::path('../composer.json');
    }
}
