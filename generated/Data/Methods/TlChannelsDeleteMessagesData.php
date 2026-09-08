<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.deleteMessages (crc32 84c1fd4e), returns messages.AffectedMessages. */
final class TlChannelsDeleteMessagesData extends Data
{
    public const METHOD = 'channels.deleteMessages';

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
