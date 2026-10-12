<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Message;

use MeRezaRezaei\Teleframe\Core\Entities\EntityParser;
use MeRezaRezaei\Teleframe\Message\MarkdownEntityParser;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * W-17 regression: the `Message\EntityParser` vs `Core\Entities\EntityParser`
 * name collision is gone. The Message-layer conventional-input adapter is now
 * `MarkdownEntityParser`; the engine's DOM / MarkdownV2 parser stays
 * `Core\Entities\EntityParser`. Both share one documented wire contract
 * (`{'_','offset','length'}` in UTF-16 units), asserted here so they cannot
 * drift.
 */
final class EntityParserW17Test extends TestCase
{
    public function test_no_ambiguous_bare_entityparser_import_remains_in_src(): void
    {
        $hits = [];
        foreach ($this->srcFiles() as $file) {
            $contents = (string) file_get_contents($file->getPathname());
            if (str_contains($contents, 'Teleframe\Message\EntityParser')) {
                $hits[] = $file->getPathname();
            }
        }

        self::assertSame([], $hits, 'the Message-layer EntityParser name must be fully retired');
    }

    public function test_message_layer_class_is_named_markdown_entity_parser(): void
    {
        $reflection = new \ReflectionClass(MarkdownEntityParser::class);

        self::assertSame('MarkdownEntityParser', $reflection->getShortName());
        self::assertSame(
            '/src/Teleframe/Message/MarkdownEntityParser.php',
            substr(str_replace('\\', '/', $reflection->getFileName()), -strlen('/src/Teleframe/Message/MarkdownEntityParser.php')),
        );
    }

    public function test_core_parser_keeps_its_name_and_contract_surface(): void
    {
        self::assertSame('EntityParser', (new \ReflectionClass(EntityParser::class))->getShortName());

        foreach (['htmlToEntities', 'markdownToEntities', 'getUtf16Length'] as $method) {
            $reflection = new \ReflectionMethod(EntityParser::class, $method);
            self::assertTrue($reflection->isPublic(), "Core EntityParser::{$method} must stay public");
            self::assertTrue($reflection->isStatic(), "Core EntityParser::{$method} must stay static");
        }
    }

    public function test_adapter_html_delegates_to_the_core_parser(): void
    {
        $adapter = MarkdownEntityParser::html('<b>Hello</b>');
        $core = EntityParser::htmlToEntities('<b>Hello</b>');

        self::assertSame($core, $adapter);
    }

    public function test_adapter_markdown_delegates_to_the_core_parser(): void
    {
        $adapter = MarkdownEntityParser::markdown('*Hello*');
        $core = EntityParser::markdownToEntities('*Hello*');

        self::assertSame($core, $adapter);
    }

    public function test_both_parsers_emit_the_shared_wire_contract(): void
    {
        $adapter = MarkdownEntityParser::spans('Hello world', [
            ['type' => 'bold', 'offset' => 0, 'length' => 5],
        ]);
        $core = EntityParser::htmlToEntities('<b>Hello</b> world');

        foreach ([$adapter, $core] as $shape) {
            self::assertArrayHasKey('text', $shape);
            self::assertArrayHasKey('entities', $shape);
            self::assertIsString($shape['text']);
            self::assertIsList($shape['entities']);
            foreach ($shape['entities'] as $entity) {
                self::assertSame(
                    ['_', 'offset', 'length'],
                    array_keys(array_intersect_key($entity, ['_' => true, 'offset' => true, 'length' => true])),
                    'every entity carries _/offset/length and nothing else beyond declared extras',
                );
                self::assertIsString($entity['_']);
                self::assertIsInt($entity['offset']);
                self::assertIsInt($entity['length']);
                self::assertStringStartsWith('messageEntity', $entity['_']);
            }
        }
    }

    public function test_message_plan_validator_reads_extra_keys_from_the_renamed_class(): void
    {
        $path = dirname(__DIR__, 2).'/src/Teleframe/Message/MessagePlanValidator.php';
        $contents = (string) file_get_contents($path);

        self::assertStringContainsString('MarkdownEntityParser::ENTITY_EXTRA_KEYS', $contents);
        self::assertStringNotContainsString('Teleframe\Message\EntityParser', $contents);
    }

    /** @return list<SplFileInfo> */
    private function srcFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(dirname(__DIR__, 2).'/src', \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file;
            }
        }

        return $files;
    }
}
