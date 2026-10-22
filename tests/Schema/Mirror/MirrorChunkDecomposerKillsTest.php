<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use MeRezaRezaei\Teleframe\Laravel\SchemaArtifacts;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorChunkDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

final class MirrorChunkDecomposerKillsTest extends TestCase
{
    private MirrorChunkDecomposer $decomposer;

    protected function setUp(): void
    {
        parent::setUp();

        $scheme = TlParser::parseFile(dirname(__DIR__, 3).'/schema/sources/TL_telegram_v227.tl');
        $catalog = MirrorCatalog::load(SchemaArtifacts::catalog(), $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);
        $facts = new MirrorFactDecomposer($resolver, $catalog);
        $this->decomposer = new MirrorChunkDecomposer($facts, $catalog);
    }

    public function test_new_messages_invalid_entry_logical_or(): void
    {
        $chunk = [
            'new_messages' => [['foo' => 'bar']],
            'users' => [],
            'chats' => [],
        ];

        $result = $this->decomposer->decomposeChunk(42, $chunk);

        self::assertContains('new_messages: entry without a constructor — cannot place the fact', $result['clues']);
    }

    public function test_new_messages_spread_one_item(): void
    {
        $chunk = [
            'new_messages' => [
                ['_' => 'message', 'id' => 1, 'peer_id' => ['_type' => 2, '_id' => 900], 'date' => 1726000000, 'message' => 'first', 'entities' => [
                    ['_' => 'messageEntityBold', 'offset' => 0, 'length' => 2],
                ]],
            ],
            'users' => [],
            'chats' => [],
        ];

        $result = $this->decomposer->decomposeChunk(42, $chunk);

        self::assertCount(2, $result['rows']);
    }

    public function test_new_messages_accumulation_rows(): void
    {
        $chunk = [
            'new_messages' => [
                ['_' => 'message', 'id' => 1, 'peer_id' => ['_type' => 2, '_id' => 900], 'date' => 1726000000, 'message' => 'first'],
                ['_' => 'message', 'id' => 2, 'peer_id' => ['_type' => 2, '_id' => 900], 'date' => 1726000000, 'message' => 'second'],
            ],
            'users' => [],
            'chats' => [],
        ];

        $result = $this->decomposer->decomposeChunk(42, $chunk);

        self::assertCount(2, $result['rows']);
    }

    public function test_new_messages_accumulation_clues(): void
    {
        $chunk = [
            'new_messages' => [
                ['_' => 'message', 'peer_id' => ['_type' => 2, '_id' => 900], 'date' => 1726000000, 'message' => 'first'],
                ['_' => 'message', 'peer_id' => ['_type' => 2, '_id' => 900], 'date' => 1726000000, 'message' => 'second'],
            ],
            'users' => [],
            'chats' => [],
        ];

        $result = $this->decomposer->decomposeChunk(42, $chunk);

        self::assertCount(2, $result['clues']);
    }

    public function test_users_invalid_entry_logical_or(): void
    {
        $chunk = [
            'new_messages' => [],
            'users' => [['foo' => 'bar']],
            'chats' => [],
        ];

        $result = $this->decomposer->decomposeChunk(42, $chunk);

        self::assertContains('users: entry without a constructor — cannot place the fact', $result['clues']);
    }

    public function test_users_spread_one_item_prepends(): void
    {
        $chunk = [
            'new_messages' => [],
            'users' => [
                ['_' => 'user', 'id' => 101, 'verified' => true, 'usernames' => [
                    ['_' => 'username', 'username' => 'foo', 'active' => true],
                ]],
                ['_' => 'user', 'id' => 102, 'verified' => true],
            ],
            'chats' => [],
        ];

        $result = $this->decomposer->decomposeChunk(42, $chunk);

        self::assertCount(3, $result['rows']);
    }

    public function test_users_spread_one_item_truncates(): void
    {
        $chunk = [
            'new_messages' => [],
            'users' => [
                ['_' => 'user', 'id' => 101, 'verified' => true, 'usernames' => [
                    ['_' => 'username', 'username' => 'foo', 'active' => true],
                ]],
            ],
            'chats' => [],
        ];

        $result = $this->decomposer->decomposeChunk(42, $chunk);

        self::assertCount(2, $result['rows']);
    }

    public function test_users_accumulation_clues(): void
    {
        $chunk = [
            'new_messages' => [],
            'users' => [
                ['_' => 'user', 'verified' => true],
                ['_' => 'user', 'verified' => true],
            ],
            'chats' => [],
        ];

        $result = $this->decomposer->decomposeChunk(42, $chunk);

        self::assertCount(2, $result['clues']);
    }

    public function test_chats_invalid_entry_logical_or(): void
    {
        $chunk = [
            'new_messages' => [],
            'users' => [],
            'chats' => [['foo' => 'bar']],
        ];

        $result = $this->decomposer->decomposeChunk(42, $chunk);

        self::assertContains('chats: entry without a constructor — cannot place the fact', $result['clues']);
    }

    public function test_chats_spread_one_item_truncates(): void
    {
        $chunk = [
            'new_messages' => [],
            'users' => [],
            'chats' => [
                ['_' => 'chat', 'id' => 900, 'title' => 'mirror lab', 'participants_count' => 3, 'date' => 1726000000, 'version' => 1, 'photo' => ['_'=>'chatPhoto','photo_id'=>5,'stripped_thumb'=>'abc']],
            ],
        ];

        $result = $this->decomposer->decomposeChunk(42, $chunk);

        self::assertCount(2, $result['rows']);
    }

    public function test_chats_accumulation_clues(): void
    {
        $chunk = [
            'new_messages' => [],
            'users' => [],
            'chats' => [
                ['_' => 'chat', 'title' => 'x', 'participants_count' => 1, 'date' => 1, 'version' => 1],
                ['_' => 'chat', 'title' => 'y', 'participants_count' => 1, 'date' => 1, 'version' => 1],
            ],
        ];

        $result = $this->decomposer->decomposeChunk(42, $chunk);

        self::assertCount(2, $result['clues']);
    }

    public function test_cast_string_ctor(): void
    {
        $ctor = new class {
            public function __toString(): string
            {
                return 'message';
            }
        };

        $chunk = [
            'new_messages' => [
                ['_' => $ctor, 'id' => 1, 'peer_id' => ['_type' => 2, '_id' => 900], 'date' => 1726000000, 'message' => 'first'],
            ],
            'users' => [],
            'chats' => [],
        ];

        $result = $this->decomposer->decomposeChunk(42, $chunk);

        self::assertCount(1, $result['rows']);
        self::assertEmpty($result['clues']);
    }
}
