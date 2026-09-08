<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Message;

use MeRezaRezaei\Teleframe\Message\Exceptions\InvalidMessagePlanException;
use MeRezaRezaei\Teleframe\Message\MessagePlanValidator;
use PHPUnit\Framework\TestCase;

/**
 * Q14 send-time typecheck vs `MethodRegistry::get('messages.sendMessage')`:
 * reads the method's declared params, rejects unknown plan/entity/markup
 * keys with the domain exception, and normalizes the canonical plan. Pure
 * typecheck — no network, no cache.
 */
final class TypecheckTest extends TestCase
{
    public function test_valid_plan_passes_and_keeps_keys(): void
    {
        $plan = [
            'text' => 'Hi',
            'entities' => [
                ['_' => 'messageEntityMention', 'offset' => 0, 'length' => 4],
            ],
            'reply_markup' => ['inline_keyboard' => [[['text' => 'Go', 'callback_data' => 'x']]]],
        ];

        $normalized = MessagePlanValidator::assertPlanValid($plan);

        self::assertSame('Hi', $normalized['text']);
        self::assertSame('messageEntityMention', $normalized['entities'][0]['_']);
        self::assertSame('inline_keyboard', array_key_first($normalized['reply_markup']));
    }

    public function test_entities_default_to_empty_when_omitted(): void
    {
        $normalized = MessagePlanValidator::assertPlanValid(['text' => 'plain']);

        self::assertSame('plain', $normalized['text']);
        self::assertSame([], $normalized['entities']);
        self::assertArrayNotHasKey('reply_markup', $normalized);
    }

    public function test_unknown_plan_key_is_rejected(): void
    {
        $this->expectException(InvalidMessagePlanException::class);

        MessagePlanValidator::assertPlanValid(['text' => 'x', 'parse_mode' => 'HTML']);
    }

    public function test_empty_text_is_rejected(): void
    {
        $this->expectException(InvalidMessagePlanException::class);

        MessagePlanValidator::assertPlanValid(['text' => '']);
    }

    public function test_non_string_text_is_rejected(): void
    {
        $this->expectException(InvalidMessagePlanException::class);

        MessagePlanValidator::assertPlanValid(['text' => 42]);
    }

    public function test_non_array_entities_are_rejected(): void
    {
        $this->expectException(InvalidMessagePlanException::class);

        MessagePlanValidator::assertPlanValid(['text' => 'x', 'entities' => 'bold']);
    }

    public function test_entity_without_message_constructor_name_is_rejected(): void
    {
        $this->expectException(InvalidMessagePlanException::class);

        MessagePlanValidator::assertPlanValid([
            'text' => 'x',
            'entities' => [['type' => 'bold', 'offset' => 0, 'length' => 1]],
        ]);
    }

    public function test_entity_with_negative_offset_is_rejected(): void
    {
        $this->expectException(InvalidMessagePlanException::class);

        MessagePlanValidator::assertPlanValid([
            'text' => 'x',
            'entities' => [['_' => 'messageEntityBold', 'offset' => -1, 'length' => 1]],
        ]);
    }

    public function test_entity_with_zero_length_is_rejected(): void
    {
        $this->expectException(InvalidMessagePlanException::class);

        MessagePlanValidator::assertPlanValid([
            'text' => 'x',
            'entities' => [['_' => 'messageEntityBold', 'offset' => 0, 'length' => 0]],
        ]);
    }

    public function test_entity_with_unknown_extra_key_is_rejected(): void
    {
        $this->expectException(InvalidMessagePlanException::class);

        MessagePlanValidator::assertPlanValid([
            'text' => 'x',
            'entities' => [['_' => 'messageEntityBold', 'offset' => 0, 'length' => 1, 'wobble' => true]],
        ]);
    }

    public function test_non_array_reply_markup_is_rejected(): void
    {
        $this->expectException(InvalidMessagePlanException::class);

        MessagePlanValidator::assertPlanValid(['text' => 'x', 'reply_markup' => 'inline']);
    }

    public function test_reply_markup_with_unknown_key_is_rejected(): void
    {
        $this->expectException(InvalidMessagePlanException::class);

        MessagePlanValidator::assertPlanValid(['text' => 'x', 'reply_markup' => ['miniapp' => true]]);
    }

    public function test_text_link_normalization_keeps_url(): void
    {
        $normalized = MessagePlanValidator::assertPlanValid([
            'text' => 'Link',
            'entities' => [
                ['_' => 'messageEntityTextUrl', 'offset' => 0, 'length' => 4, 'url' => 'https://t.me/x'],
            ],
        ]);

        self::assertSame([
            '_' => 'messageEntityTextUrl',
            'offset' => 0,
            'length' => 4,
            'url' => 'https://t.me/x',
        ], $normalized['entities'][0]);
    }
}