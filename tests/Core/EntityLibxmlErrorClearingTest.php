<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core;

use MeRezaRezaei\Teleframe\Core\Entities\EntityParser;
use PHPUnit\Framework\TestCase;

final class EntityLibxmlErrorClearingTest extends TestCase
{
    public function test_malformed_html_leaves_no_libxml_errors_after_parsing(): void
    {
        libxml_clear_errors();
        libxml_use_internal_errors(true);

        $result = EntityParser::htmlToEntities('<b>unterminated <i>broken');

        self::assertIsArray($result);
        self::assertSame([], libxml_get_errors());
    }

    public function test_malformed_html_still_returns_text(): void
    {
        libxml_clear_errors();

        $result = EntityParser::htmlToEntities('<b>unterminated <i>broken');

        self::assertStringContainsString('unterminated', $result['text']);
        self::assertStringContainsString('broken', $result['text']);
    }

    public function test_parsing_malformed_html_does_not_raise_a_php_warning(): void
    {
        $warnings = [];
        set_error_handler(static function (int $severity, string $message) use (&$warnings): bool {
            $warnings[] = $message;

            return true;
        });

        try {
            EntityParser::htmlToEntities('<b>unterminated <i>broken');
        } finally {
            restore_error_handler();
        }

        self::assertSame([], $warnings);
    }
}
