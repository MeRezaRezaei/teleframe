<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.updateBusinessGreetingMessage (crc32 66cdafc4), returns Bool. */
final class TlAccountUpdateBusinessGreetingMessageData extends Data
{
    public const METHOD = 'account.updateBusinessGreetingMessage';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $message,
    ) {
    }
}
