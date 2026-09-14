<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorChunkDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use PHPUnit\Framework\TestCase;

/**
 * Chunk decomposer — the verbatim's "if we can ingest the big update the
 * full schema is correct" gate. A decoded updates.Difference batch must
 * decompose into rows for every embedded fact (messages, users, chats)
 * without clues.
 */
final class MirrorChunkDecomposerTest extends TestCase
{
    private MirrorChunkDecomposer $decomposer;

    protected function setUp(): void
    {
        $scheme = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        $catalog = MirrorCatalog::load(__DIR__.'/../../../docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json', $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);
        $this->decomposer = new MirrorChunkDecomposer(
            new MirrorFactDecomposer($resolver, $catalog),
            $catalog,
        );
    }

    public function test_full_chunk_decomposes_all_fact_kinds(): void
    {
        $chunk = [
            '_' => 'updates.difference',
            'new_messages' => [
                [
                    '_' => 'message',
                    'id' => 1,
                    'peer_id' => ['_type' => 2, '_id' => 900],
                    'date' => 1726000000,
                    'message' => 'first',
                ],
            ],
            'users' => [
                ['_' => 'user', 'id' => 101, 'verified' => true],
            ],
            'chats' => [
                ['_' => 'chat', 'id' => 900, 'title' => 'mirror lab'],
            ],
            'state' => ['_' => 'updates.state', 'pts' => 10, 'qts' => 0, 'date' => 1726000000, 'seq' => 0],
        ];

        $result = $this->decomposer->decomposeChunk(42, $chunk);

        $tables = array_map(fn (array $r) => $r['table'], $result['rows']);
        self::assertContains('tf_messages', $tables, 'new_messages → tf_messages');
        self::assertContains('tf_users', $tables, 'users → tf_users');
        self::assertContains('tf_chats', $tables, 'chats → tf_chats');
        self::assertEmpty($result['clues'], 'a well-formed chunk decomposes without clues');
    }

    public function test_service_message_routes_to_service_table(): void
    {
        $chunk = [
            '_' => 'updates.difference',
            'new_messages' => [
                [
                    '_' => 'messageService',
                    'id' => 2,
                    'peer_id' => ['_type' => 2, '_id' => 900],
                    'date' => 1726000000,
                ],
            ],
            'users' => [],
            'chats' => [],
        ];

        $result = $this->decomposer->decomposeChunk(42, $chunk);

        $tables = array_map(fn (array $r) => $r['table'], $result['rows']);
        self::assertContains('tf_messages_service', $tables, 'messageService ctor routes to the splitter sibling table');
        self::assertEmpty($result['clues']);
    }

    public function test_unknown_ctor_in_chunk_is_clue(): void
    {
        $chunk = [
            '_' => 'updates.difference',
            'new_messages' => [['_' => 'notAMessage']],
            'users' => [],
            'chats' => [],
        ];

        $result = $this->decomposer->decomposeChunk(42, $chunk);

        self::assertNotEmpty($result['clues'], 'an unknown constructor must surface as an ingest clue');
        self::assertStringContainsString('notAMessage', $result['clues'][0]);
    }
}
