<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use MeRezaRezaei\Teleframe\Laravel\SchemaArtifacts;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorNestedSourceWalker;
use PHPUnit\Framework\TestCase;

final class MirrorNestedSourceWalkerKillsTest extends TestCase
{
    private MirrorNestedSourceWalker $walker;

    protected function setUp(): void
    {
        parent::setUp();

        $scheme = TlParser::parseFile(dirname(__DIR__, 3).'/schema/sources/TL_telegram_v227.tl');
        $catalog = MirrorCatalog::load(SchemaArtifacts::catalog(), $scheme);
        $this->walker = new MirrorNestedSourceWalker($catalog);
    }

    /** Kills mutant 180 (line 77 Continue_ -> Break). */
    public function test_absent_first_source_does_not_abort_later_sources(): void
    {
        $payload = [
            '_' => 'updateChannelParticipant',
            'channel_id' => 1001234567890,
            'new_participant' => null,
            'prev_participant' => [
                '_' => 'channelParticipantSelf',
                'user_id' => 7,
                'date' => 1690000000,
            ],
        ];

        $facts = $this->walker->collect($payload);
        self::assertCount(1, $facts, 'prev_participant must still be collected when new_participant is absent');
        self::assertSame('tf_channel_participants', $facts[0]['table']);
        self::assertSame('channelParticipantSelf', $facts[0]['payload']['_']);
    }

    /** Kills mutant 181 (line 89 LogicalAnd -> ||). */
    public function test_vector_source_skips_non_constructor_items(): void
    {
        $payload = [
            '_' => 'messages.dialogs',
            'chats' => [0 => ['foo' => 'bar']],
        ];

        self::assertSame([], $this->walker->collect($payload));
    }

    /** Kills mutant 182 (line 97 LogicalAnd -> ||). */
    public function test_single_source_value_must_be_constructor(): void
    {
        $payload = [
            '_' => 'messageMediaDocument',
            'document' => ['foo' => 'bar'],
        ];

        self::assertSame([], $this->walker->collect($payload));
    }

    /** Kills mutant 183 (line 108 Continue_ -> Break). */
    public function test_wrapper_descent_continues_past_null_param(): void
    {
        $payload = [
            '_' => 'messages.dialogs',
            'dialogs' => null,
            'messages' => [0 => [
                '_' => 'message',
                'media' => ['_' => 'messageMediaDocument', 'document' => ['_' => 'document']],
            ]],
        ];

        $facts = $this->walker->collect($payload);
        self::assertGreaterThanOrEqual(1, count($facts));
    }

    /** Kills mutant 188 (line 117 Continue_ -> Break). */
    public function test_wrapper_descent_continues_past_vector_param(): void
    {
        $payload = [
            '_' => 'messages.dialogs',
            'dialogs' => [0 => ['foo' => 'bar']],
            'messages' => [0 => [
                '_' => 'message',
                'media' => ['_' => 'messageMediaDocument', 'document' => ['_' => 'document']],
            ]],
        ];

        $facts = $this->walker->collect($payload);
        self::assertGreaterThanOrEqual(1, count($facts));
    }

    /** Kills mutant 197 (line 261 Foreach_). */
    public function test_items_actually_iterates_keys(): void
    {
        $payload = [
            '_' => 'messages.dialogs',
            'chats' => [0 => ['_' => 'chat', 'id' => 1]],
        ];

        $facts = $this->walker->collect($payload);
        self::assertNotEmpty($facts);
    }

    /** Kills mutant 198 (line 263 ReturnRemoval). */
    public function test_items_rejects_non_integer_keys(): void
    {
        $payload = [
            '_' => 'messages.dialogs',
            'chats' => ['foo' => ['_' => 'chat', 'id' => 1]],
        ];

        self::assertSame([], $this->walker->collect($payload));
    }
}
