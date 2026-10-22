<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use FilesystemIterator;
use MeRezaRezaei\Teleframe\Schema\Generator\DtoGenerator;
use MeRezaRezaei\Teleframe\Schema\Generator\TeleframeSchemeLoader;
use ParseError;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * W-01: a generic `{X:Type}` / `{t:Type}` function param must never become a
 * brace-mangled promoted property (`public mixed ${X,` — a PHP parse error).
 * The loader now maps generic-arg tokens to kind='generic' TlParams (skipped
 * by generators) and DtoGenerator skips any non-identifier param name as a
 * second line of defence.
 */
final class SchemaFixesW01Test extends TestCase
{
    private const PACKAGE_ROOT = __DIR__.'/../..';

    public static function setUpBeforeClass(): void
    {
        require self::PACKAGE_ROOT.'/tests/Schema/FixesAutoload.php';
    }

    public function test_generic_function_param_does_not_emit_brace_mangled_property(): void
    {
        $scheme = TeleframeSchemeLoader::parseString(
            "---functions---\ninvokeAfterMsgs#3dc4b4f0 {X:Type} msg_ids:Vector<long> query:!X = X;\n",
        );
        $content = implode("\n", array_values((new DtoGenerator)->generate($scheme)));

        self::assertStringContainsString('public array $msgIds', $content);
        self::assertStringContainsString('public mixed $query', $content);
        self::assertStringNotContainsString('${', $content, 'generic {X:Type} param leaked into the DTO');
    }

    public function test_generic_type_params_do_not_emit_brace_mangled_properties(): void
    {
        $scheme = TeleframeSchemeLoader::parseString(
            "---types---\nboxedBool#cb79d131 {t:Type} retval:!t = Bool;\n",
        );
        $content = implode("\n", array_values((new DtoGenerator)->generate($scheme)));

        self::assertStringNotContainsString('${', $content, 'generic {t:Type} param leaked into the DTO');
    }

    public function test_every_generated_artifact_parses(): void
    {
        $root = self::PACKAGE_ROOT.'/src/Schema/Generated';
        self::assertDirectoryExists($root);

        $count = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file->isDir() || ! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }
            $count++;
            $code = (string) file_get_contents($file->getPathname());
            try {
                token_get_all($code, TOKEN_PARSE);
            } catch (ParseError $e) {
                self::fail(sprintf('parse error in %s: %s', $file->getPathname(), $e->getMessage()));
            }
            self::assertStringNotContainsString('${', $code, "brace-mangled property in {$file->getPathname()}");
        }

        self::assertGreaterThan(1000, $count, 'the committed generated tree should be fully enumerated');
    }

    /** The eleven W-01 victims must be present, parseable and mangle-free. */
    public function test_previously_broken_invoke_dto_files_are_fixed(): void
    {
        $dir = self::PACKAGE_ROOT.'/src/Schema/Generated/Data/Methods';
        $names = [
            'InvokeAfterMsgData',
            'InvokeAfterMsgsData',
            'InitConnectionData',
            'InvokeWithApnsSecretData',
            'InvokeWithBusinessConnectionData',
            'InvokeWithGooglePlayIntegrityData',
            'InvokeWithLayerData',
            'InvokeWithMessagesRangeData',
            'InvokeWithReCaptchaData',
            'InvokeWithTakeoutData',
            'InvokeWithoutUpdatesData',
        ];
        foreach ($names as $name) {
            $path = "{$dir}/{$name}.php";
            self::assertFileExists($path);
            $code = (string) file_get_contents($path);
            self::assertStringNotContainsString('${', $code, "{$name} still brace-mangled");
            token_get_all($code, TOKEN_PARSE);
        }
    }
}
