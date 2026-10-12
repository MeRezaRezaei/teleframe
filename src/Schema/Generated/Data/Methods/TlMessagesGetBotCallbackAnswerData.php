<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getBotCallbackAnswer (crc32 9342ca07), returns messages.BotCallbackAnswer. */
final class TlMessagesGetBotCallbackAnswerData extends Data
{
    public const METHOD = 'messages.getBotCallbackAnswer';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $game,
    public mixed $peer,
    public int $msgId,
    public ?string $data,
    public mixed $password,
    ) {
    }
}
