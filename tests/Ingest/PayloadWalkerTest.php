<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use MeRezaRezaei\Teleframe\Ingest\PayloadWalker;
use PHPUnit\Framework\TestCase;

final class PayloadWalkerTest extends TestCase
{
    /**
     * @return list<array<string, mixed>>
     */
    private function walkAll(array $payload): array
    {
        return iterator_to_array(PayloadWalker::walk($payload), false);
    }

    public function test_flat_user_yields_single_root_node(): void
    {
        $user = [
            '_' => 'user',
            'flags' => 257,
            'id' => 501558149,
            'access_hash' => '1234567890123456789',
            'first_name' => 'Reza',
            'last_name' => 'Rezaei',
            'username' => 'rezarezaei',
            'premium' => true,
            'restriction_reason' => null,
        ];

        $nodes = $this->walkAll($user);

        self::assertCount(1, $nodes);
        self::assertSame(
            [
                'constructor' => 'user',
                'payload' => $user,
                'path' => 'user',
                'parent_param' => null,
                'is_root' => true,
                'vector_index' => null,
            ],
            $nodes[0],
        );
    }

    public function test_nested_photo_object_yields_child_with_parent_param(): void
    {
        $photo = [
            '_' => 'userProfilePhoto',
            'has_video' => false,
            'photo_id' => '55660011',
            'stripped_thumb' => "\x01\x02",
            'dc_id' => 4,
        ];
        $user = [
            '_' => 'user',
            'flags' => 131072,
            'id' => 501558149,
            'access_hash' => '1234567890123456789',
            'first_name' => 'Reza',
            'photo' => $photo,
        ];

        $nodes = $this->walkAll($user);

        self::assertSame(
            [
                [
                    'constructor' => 'user',
                    'payload' => $user,
                    'path' => 'user',
                    'parent_param' => null,
                    'is_root' => true,
                    'vector_index' => null,
                ],
                [
                    'constructor' => 'userProfilePhoto',
                    'payload' => $photo,
                    'path' => 'user.photo',
                    'parent_param' => 'photo',
                    'is_root' => false,
                    'vector_index' => null,
                ],
            ],
            $nodes,
        );
    }

    public function test_vector_of_scalars_yields_no_nodes(): void
    {
        $user = [
            '_' => 'user',
            'flags' => 4194304,
            'id' => 501558149,
            'access_hash' => '1234567890123456789',
            'first_name' => 'Reza',
            'restriction_reason' => ['spam', 'ads'],
        ];

        $nodes = $this->walkAll($user);

        self::assertCount(1, $nodes);
        self::assertSame('user', $nodes[0]['constructor']);
        self::assertSame(
            [],
            array_filter(
                $nodes,
                static fn (array $node): bool => $node['parent_param'] === 'restriction_reason',
            ),
        );
    }

    public function test_vector_of_objects_links_parent_param_and_vector_index(): void
    {
        $first = ['_' => 'username', 'flags' => 1, 'username' => 'reza', 'active' => true];
        $second = ['_' => 'username', 'flags' => 0, 'username' => 'reza_alt', 'active' => false];
        $user = [
            '_' => 'user',
            'flags' => 1052672,
            'id' => 501558149,
            'access_hash' => '1234567890123456789',
            'first_name' => 'Reza',
            'usernames' => [$first, 'collectible_alias', $second],
        ];

        $nodes = $this->walkAll($user);

        self::assertSame(
            [
                [
                    'constructor' => 'user',
                    'payload' => $user,
                    'path' => 'user',
                    'parent_param' => null,
                    'is_root' => true,
                    'vector_index' => null,
                ],
                [
                    'constructor' => 'username',
                    'payload' => $first,
                    'path' => 'user.usernames.0',
                    'parent_param' => 'usernames',
                    'is_root' => false,
                    'vector_index' => 0,
                ],
                [
                    'constructor' => 'username',
                    'payload' => $second,
                    'path' => 'user.usernames.2',
                    'parent_param' => 'usernames',
                    'is_root' => false,
                    'vector_index' => 2,
                ],
            ],
            $nodes,
        );
    }

    public function test_conditional_absent_key_yields_nothing_extra(): void
    {
        $user = [
            '_' => 'user',
            'flags' => 0,
            'id' => 501558149,
            'access_hash' => '1234567890123456789',
            'first_name' => 'Reza',
        ];

        $nodes = $this->walkAll($user);

        self::assertCount(1, $nodes);
        self::assertSame('user', $nodes[0]['path']);
        self::assertArrayNotHasKey('last_name', $user);
        self::assertArrayNotHasKey('photo', $user);
    }

    public function test_deep_chain_message_peer_chat_orders_parents_before_children(): void
    {
        $chatPhoto = ['_' => 'chatPhoto', 'has_video' => false, 'photo_id' => '9911', 'dc_id' => 2];
        $chat = [
            '_' => 'chat',
            'flags' => 0,
            'id' => 100200300,
            'title' => 'Deep Chat',
            'photo' => $chatPhoto,
            'participants_count' => 12,
        ];
        $peer = [
            '_' => 'peer',
            'chat' => $chat,
        ];
        $message = [
            '_' => 'message',
            'flags' => 257,
            'id' => 42,
            'peer_id' => $peer,
            'date' => 1756362000,
            'message' => 'hello',
        ];

        $nodes = $this->walkAll($message);

        self::assertSame(
            [
                'message',
                'message.peer_id',
                'message.peer_id.chat',
                'message.peer_id.chat.photo',
            ],
            array_column($nodes, 'path'),
        );
        self::assertSame(
            ['message', 'peer', 'chat', 'chatPhoto'],
            array_column($nodes, 'constructor'),
        );
        self::assertSame(
            [null, 'peer_id', 'chat', 'photo'],
            array_column($nodes, 'parent_param'),
        );
        self::assertSame(
            [true, false, false, false],
            array_column($nodes, 'is_root'),
        );
        self::assertSame(
            [null, null, null, null],
            array_column($nodes, 'vector_index'),
        );
        self::assertSame($chatPhoto, $nodes[3]['payload']);
    }

    public function test_walk_returns_lazy_generator(): void
    {
        $generator = PayloadWalker::walk(['_' => 'userEmpty']);

        self::assertInstanceOf(\Generator::class, $generator);
        self::assertSame('userEmpty', $generator->current()['constructor']);
    }
}
