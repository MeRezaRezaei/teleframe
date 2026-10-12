<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\MTProto\TL;

use MeRezaRezaei\Teleframe\Core\MTProto\TL\ParsedSignature;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\Schema\UserScopeSchema;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLSignatureParser;
use PHPUnit\Framework\TestCase;

/**
 * W-35: direct units for ParsedSignature (the immutable parse result) and
 * the UserScopeSchema generated LINES constant. Every documented scope line
 * must tokenize cleanly, carry its official #id verbatim, and expose a
 * consistent name/returnType. No regex anywhere (src rule mirrored here).
 */
final class ParsedSignatureUserScopeTest extends TestCase
{
    public function test_parse_extracts_name_id_fields_and_return_type(): void
    {
        $sig = TLSignatureParser::parse('account.checkUsername#2714d86c username:string = Bool');

        self::assertInstanceOf(ParsedSignature::class, $sig);
        self::assertSame('account.checkUsername', $sig->name);
        self::assertSame(0x2714D86C, $sig->id);
        self::assertTrue($sig->hasExplicitId);
        self::assertSame('Bool', $sig->returnType);
        self::assertSame(
            [['name' => 'username', 'type' => 'string', 'flagWord' => null, 'bit' => null]],
            $sig->fields,
        );
    }

    public function test_parse_without_explicit_id_marks_absent(): void
    {
        $sig = TLSignatureParser::parse('auth.sendCode phone_number:string = Bool');

        self::assertSame('auth.sendCode', $sig->name);
        self::assertFalse($sig->hasExplicitId);
        self::assertSame('Bool', $sig->returnType);
    }

    public function test_parsed_signature_is_immutable(): void
    {
        $sig = TLSignatureParser::parse('account.checkUsername#2714d86c username:string = Bool');

        $this->expectException(\Error::class); // readonly property write on frozen object
        /** @phpstan-ignore-next-line intentional runtime immutability probe */
        $sig->name = 'mutated';
    }

    public function test_user_scope_schema_lines_are_non_empty_strings(): void
    {
        self::assertNotSame([], UserScopeSchema::LINES);
        foreach (UserScopeSchema::LINES as $i => $line) {
            self::assertIsString($line, "LINES[{$i}] must be a string");
            self::assertNotSame('', trim($line), "LINES[{$i}] must be non-empty");
            self::assertStringContainsString('=', $line, "LINES[{$i}] must have a return type");
        }
    }

    public function test_every_user_scope_line_parses_with_official_id(): void
    {
        foreach (UserScopeSchema::LINES as $line) {
            $sig = TLSignatureParser::parse($line);

            // Every scope line is generated WITH its official #id verbatim.
            self::assertTrue($sig->hasExplicitId, "missing #id in: {$line}");
            self::assertNotSame(0, $sig->id, "zero id in: {$line}");
            self::assertNotSame('', $sig->returnType, "empty return type in: {$line}");
            self::assertStringContainsString('#', $line);
        }
    }

    public function test_known_scope_method_keeps_official_id_and_shape(): void
    {
        $byName = [];
        foreach (UserScopeSchema::LINES as $line) {
            $sig = TLSignatureParser::parse($line);
            $byName[$sig->name] = $sig;
        }

        $check = $byName['account.checkUsername'] ?? null;
        self::assertNotNull($check);
        self::assertSame(0x2714D86C, $check->id);
        self::assertSame('Bool', $check->returnType);
        self::assertCount(1, $check->fields);

        $send = $byName['auth.sendCode'] ?? null;
        self::assertNotNull($send);
        self::assertSame(0xA677244F, $send->id);
        self::assertCount(4, $send->fields); // phone_number, api_id, api_hash, settings
        self::assertSame('auth.SentCode', $send->returnType);

        // No duplicate names in the generated catalog.
        $names = array_map(static fn (string $line): string => substr($line, 0, strpos($line, '#')), UserScopeSchema::LINES);
        self::assertSame($names, array_values(array_unique($names)), 'duplicate constructor names in UserScopeSchema');
    }
}
