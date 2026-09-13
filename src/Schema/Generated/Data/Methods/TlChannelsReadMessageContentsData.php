<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.readMessageContents (crc32 eab5dc38), returns Bool. */
final class TlChannelsReadMessageContentsData extends Data
{
    public const METHOD = 'channels.readMessageContents';

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
