<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.setBotCallbackAnswer (crc32 d58f130a), returns Bool. */
final class TlMessagesSetBotCallbackAnswerData extends Data
{
    public const METHOD = 'messages.setBotCallbackAnswer';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $alert,
    public int $queryId,
    public ?string $message,
    public ?string $url,
    public int $cacheTime,
    ) {
    }
}
