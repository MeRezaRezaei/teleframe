<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use MeRezaRezaei\Teleframe\Laravel\SchemaArtifacts;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCtorSplitter;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

final class MirrorCtorSplitterKillsTest extends TestCase
{
    private const TL = __DIR__.'/../../../schema/sources/TL_telegram_v227.tl';

    private TlScheme $scheme;

    private array $entries;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scheme = TlParser::parseFile(self::TL);
        $this->entries = json_decode(
            (string) file_get_contents(SchemaArtifacts::catalog()),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    private function splitter(): MirrorCtorSplitter
    {
        return new MirrorCtorSplitter($this->scheme);
    }

    public function test_real_catalog_split_produces_service_sibling(): void
    {
        $split = $this->splitter()->apply($this->entries);

        self::assertArrayHasKey('tf_messages_service', $split);
        self::assertSame(['messageService'], $split['tf_messages_service']['ctors']);
        self::assertSame('Message', $split['tf_messages_service']['tl']);
        self::assertNotContains('messageService', $split['tf_messages']['ctors']);
        self::assertContains('message', $split['tf_messages']['ctors']);

        self::assertSame(
            [
                ['id', 'INTEGER NOT NULL'],
                ['peer_id', 'peer_type TINYINT + peer_id BIGINT'],
                ['date', 'INTEGER NOT NULL'],
            ],
            $split['tf_messages_service']['base'],
        );

        self::assertSame(
            ['out', 'mentioned', 'media_unread', 'reactions_are_possible', 'silent', 'post', 'legacy'],
            $split['tf_messages_service']['bools'],
        );

        self::assertSame(
            [
                ['from_id', 'peer_type TINYINT + peer_id BIGINT'],
                ['saved_peer_id', 'peer_type TINYINT + peer_id BIGINT'],
                ['reply_to', 'FK→MessageReplyHeader'],
                ['reactions', 'FK→MessageReactions'],
                ['ttl_period', 'INTEGER NOT NULL'],
                ['action', 'FK→MessageAction'],
            ],
            $split['tf_messages_service']['children'],
        );

        self::assertSame(['peer_type', 'peer_id'], $split['tf_messages_service']['scoped_by']);
    }

    public function test_synthetic_append_loop_adds_uncovered_ref_params(): void
    {
        $entry = [
            'tl' => 'Message',
            'ctors' => ['message', 'messageService'],
            'base' => [],
            'bools' => [],
            'children' => [],
        ];
        $catalog = ['tf_messages' => $entry];

        $split = $this->splitter()->apply($catalog);

        $children = $split['tf_messages_service']['children'];
        $names = array_column($children, 0);
        self::assertContains('from_id', $names);
        self::assertContains('peer_id', $names);
        self::assertContains('saved_peer_id', $names);
        self::assertContains('reply_to', $names);
        self::assertContains('action', $names);
        self::assertContains('reactions', $names);

        foreach ($children as $child) {
            if (in_array($child[0], ['from_id', 'peer_id', 'saved_peer_id', 'reply_to', 'action', 'reactions'], true)) {
                self::assertSame('FK→'.match ($child[0]) {
                    'from_id', 'peer_id', 'saved_peer_id' => 'Peer',
                    'reply_to' => 'MessageReplyHeader',
                    'action' => 'MessageAction',
                    'reactions' => 'MessageReactions',
                }, $child[1]);
            }
        }

        self::assertArrayNotHasKey('scoped_by', $split['tf_messages_service']);
    }

    public function test_synthetic_append_with_some_covered(): void
    {
        $entry = [
            'tl' => 'Message',
            'ctors' => ['message', 'messageService'],
            'base' => [],
            'bools' => [],
            'children' => [['reply_to', 'FK→MessageReplyHeader']],
        ];
        $catalog = ['tf_messages' => $entry];

        $split = $this->splitter()->apply($catalog);

        $children = $split['tf_messages_service']['children'];
        $names = array_column($children, 0);
        // reply_to covered by parent -> peer shape? no, stays FK entry from parent
        self::assertContains('reply_to', $names);
        self::assertContains('from_id', $names);
        self::assertContains('peer_id', $names);
        // reply_to should appear once
        self::assertSame(1, count(array_filter($names, fn ($n) => $n === 'reply_to')));
    }

    public function test_base_filter_gap_preserves_keys_via_array_values(): void
    {
        $entry = [
            'tl' => 'Message',
            'ctors' => ['message', 'messageService'],
            'base' => [
                ['foo', 'TEXT NOT NULL'],
                ['id', 'INTEGER NOT NULL'],
            ],
            'bools' => [],
            'children' => [],
        ];
        $catalog = ['tf_messages' => $entry];

        $split = $this->splitter()->apply($catalog);

        self::assertSame([['id', 'INTEGER NOT NULL']], $split['tf_messages_service']['base']);
    }

    public function test_ctor_null_returns_catalog_unchanged(): void
    {
        // scheme without Message type: use a fresh empty scheme
        $emptyScheme = new TlScheme(227);
        $splitter = new MirrorCtorSplitter($emptyScheme);
        $catalog = ['tf_messages' => $this->entries['tf_messages']];
        $out = $splitter->apply($catalog);
        self::assertSame($catalog, $out);
        self::assertArrayNotHasKey('tf_messages_service', $out);
    }

    public function test_missing_catalog_key_returns_unchanged(): void
    {
        $split = $this->splitter()->apply(['tf_users' => $this->entries['tf_messages']]);
        self::assertArrayNotHasKey('tf_messages_service', $split);
    }

    public function test_parent_ctrs_filtered(): void
    {
        $entry = [
            'tl' => 'Message',
            'ctors' => ['messageService', 'message'],
            'base' => [['id', 'INTEGER NOT NULL']],
            'bools' => [],
            'children' => [],
        ];
        $catalog = ['tf_messages' => $entry];
        $split = $this->splitter()->apply($catalog);
        self::assertSame(['message'], $split['tf_messages']['ctors']);
    }

    public function test_scoped_by_inherited_with_non_service_before_service(): void
    {
        $entry = [
            'tl' => 'Message',
            'ctors' => ['message', 'messageService'],
            'base' => [
                ['foo', 'TEXT NOT NULL'],
                ['peer_id', 'peer_type TINYINT + peer_id BIGINT'],
            ],
            'bools' => [],
            'children' => [],
            'scoped_by' => ['peer_type'],
        ];
        $catalog = ['tf_messages' => $entry];
        $split = $this->splitter()->apply($catalog);
        self::assertSame(['peer_type'], $split['tf_messages_service']['scoped_by']);
    }

    public function test_scoped_by_single_produced_column(): void
    {
        $entry = [
            'tl' => 'Message',
            'ctors' => ['message', 'messageService'],
            'base' => [
                ['id', 'INTEGER NOT NULL'],
            ],
            'bools' => [],
            'children' => [],
            'scoped_by' => ['id'],
        ];
        $catalog = ['tf_messages' => $entry];
        $split = $this->splitter()->apply($catalog);
        self::assertSame(['id'], $split['tf_messages_service']['scoped_by']);
    }

    public function test_scoped_by_duplicates_collapsed(): void
    {
        $entry = [
            'tl' => 'Message',
            'ctors' => ['message', 'messageService'],
            'base' => [
                ['peer_id', 'peer_type TINYINT + peer_id BIGINT'],
            ],
            'bools' => [],
            'children' => [],
            'scoped_by' => ['peer_type', 'peer_type', 'peer_id'],
        ];
        $catalog = ['tf_messages' => $entry];
        $split = $this->splitter()->apply($catalog);
        self::assertSame(['peer_type', 'peer_id'], $split['tf_messages_service']['scoped_by']);
    }

    public function test_no_scoped_by_when_parent_lacks(): void
    {
        $entry = [
            'tl' => 'Message',
            'ctors' => ['message', 'messageService'],
            'base' => [
                ['id', 'INTEGER NOT NULL'],
            ],
            'bools' => [],
            'children' => [],
        ];
        $catalog = ['tf_messages' => $entry];
        $split = $this->splitter()->apply($catalog);
        self::assertArrayNotHasKey('scoped_by', $split['tf_messages_service']);
    }
}
