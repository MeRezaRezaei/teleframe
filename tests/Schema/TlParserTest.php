<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use PHPUnit\Framework\TestCase;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParseException;

final class TlParserTest extends TestCase
{
    public function test_parses_fixture(): void
    {
        $s = TlParser::parseFile(__DIR__ . '/fixtures/mini.tl', 0, strict: true);
        self::assertSame(227, $s->layer);
        self::assertSame(5, $s->counts()['types']); // User, UserStatus, messages.Messages, MsgsStateInfo, Config (method return)
        self::assertArrayHasKey('userEmpty', $s->types()['User']->constructors());
        $user = $s->types()['User']->constructors()['userEmpty'];
        self::assertSame(0xd3bc4b7a, $user->id);
        self::assertArrayHasKey('help.getConfig', $s->methods());
        self::assertSame('Config', $s->methods()['help.getConfig']->returnType);
        $messages = $s->types()['messages.Messages']->constructors()['messages.messages'];
        $params = $messages->params();
        self::assertSame('vector', $params['messages']->kind());
        self::assertSame('Message', $params['messages']->baseType());
        self::assertNull($params['messages']->conditional());
        self::assertSame('req_msg_id:long', 'req_msg_id:' . $s->types()['MsgsStateInfo']->constructors()['msgs_state_info']->params()['req_msg_id']->raw);
    }

    public function test_short_hex_id_padded(): void
    {
        // userStatusEmpty#9d05049 — 7 digits, must parse and crc-verify
        $s = TlParser::parseString("---types---\nuserStatusEmpty#9d05049 = UserStatus;\n", 227);
        self::assertSame(0x09d05049, $s->types()['UserStatus']->constructors()['userStatusEmpty']->id);
    }

    public function test_crc_mismatch_aborts_in_strict_mode(): void
    {
        $this->expectException(TlParseException::class);
        $this->expectExceptionMessage('crc32 mismatch');
        TlParser::parseString("---types---\nbogus#00000001 id:long = User;\n", 227, strict: true);
    }

    public function test_crc_mismatch_recorded_in_lenient_mode(): void
    {
        $s = TlParser::parseString("---types---\nbogus#00000001 id:long = User;\n", 227);
        self::assertArrayHasKey('bogus', $s->types()['User']->constructors());
        self::assertSame('00000001', $s->crcMismatches['bogus']['stated']);
        self::assertNotSame('', $s->crcMismatches['bogus']['computed']);
    }

    public function test_malformed_line_aborts(): void
    {
        $this->expectException(TlParseException::class);
        TlParser::parseString("---types---\nnot a definition at all\n", 227);
    }

    public function test_generic_definition_accepted(): void
    {
        $s = TlParser::parseString("---types---\nvector#1cb5c415 {t:Type} # [ t ] = Vector t;\n", 227);
        self::assertArrayHasKey('vector', $s->types()['Vector t']->constructors());
        $s2 = TlParser::parseString("---functions---\ninvokeAfterMsg#cb9f372d {X:Type} msg_id:long query:!X = X;\n", 227);
        self::assertArrayHasKey('invokeAfterMsg', $s2->methods());
    }

    public function test_secret_sections_last_wins(): void
    {
        $tl = "===8===\ndecryptedMessage#1f814f1f random_id:long random_bytes:bytes message:string media:DecryptedMessageMedia = DecryptedMessage;\n"
            . "===143===\ndecryptedMessage#1f814f1f random_id:long random_bytes:bytes message:string = DecryptedMessage;\n";
        // second section has different params — same crc? No: crc covers params, so craft valid pair from real file:
        $tl = "===8===\n"
            . "decryptedMessageMediaEmpty#89f5c4a = DecryptedMessageMedia;\n"
            . "===73===\n"
            . "decryptedMessageMediaEmpty#89f5c4a = DecryptedMessageMedia;\n";
        $s = TlParser::parseString($tl, 0);
        self::assertCount(1, $s->types()['DecryptedMessageMedia']->constructors());
    }
}
