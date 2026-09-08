<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.toggleNoForwards (crc32 b2081a35), returns Updates. */
final class TlMessagesToggleNoForwardsData extends Data
{
    public const METHOD = 'messages.toggleNoForwards';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public mixed $enabled,
    public ?int $requestMsgId,
    ) {
    }
}
