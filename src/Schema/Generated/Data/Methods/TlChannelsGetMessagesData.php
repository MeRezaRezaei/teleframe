<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.getMessages (crc32 ad8c9a23), returns messages.Messages. */
final class TlChannelsGetMessagesData extends Data
{
    public const METHOD = 'channels.getMessages';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public array $id,
    ) {
    }
}
