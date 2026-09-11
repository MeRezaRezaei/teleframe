<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5CtorSplitter;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

final class Nf5CtorSplitterTest extends TestCase
{
    private function catalog(): array
    {
        $json = file_get_contents(__DIR__.'/../../../docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json');
        self::assertNotFalse($json);
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_message_service_is_synthesised(): void
    {
        $scheme = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        $out = (new Nf5CtorSplitter($scheme))->apply($this->catalog());

        self::assertArrayHasKey('tf_messages_service', $out);
        $svc = $out['tf_messages_service'];

        self::assertSame('Message', $svc['tl']);
        self::assertSame(['messageService'], $svc['ctors']);
        self::assertSame([['id', 'INTEGER NOT NULL'], ['peer_id', 'peer_type TINYINT + peer_id BIGINT'], ['date', 'INTEGER NOT NULL']], $svc['base']);
        self::assertSame(['out', 'mentioned', 'media_unread', 'reactions_are_possible', 'silent', 'post', 'legacy'], $svc['bools']);
        $childNames = array_column($svc['children'], 0);
        self::assertContains('action', $childNames);
    }

    public function test_source_entry_is_trimmed(): void
    {
        $scheme = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        $out = (new Nf5CtorSplitter($scheme))->apply($this->catalog());

        self::assertSame(['messageEmpty', 'message'], $out['tf_messages']['ctors']);
        $childNames = array_column($out['tf_messages']['children'], 0);
        self::assertNotContains('action', $childNames);
        self::assertNotContains('reactions_are_possible', $out['tf_messages']['bools']);
    }

    public function test_nondestructive_for_untouched_tables(): void
    {
        $scheme = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        $out = (new Nf5CtorSplitter($scheme))->apply($this->catalog());
        self::assertSame($this->catalog()['tf_users'], $out['tf_users']);
    }
}
