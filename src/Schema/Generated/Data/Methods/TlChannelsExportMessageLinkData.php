<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.exportMessageLink (crc32 e63fadeb), returns ExportedMessageLink. */
final class TlChannelsExportMessageLinkData extends Data
{
    public const METHOD = 'channels.exportMessageLink';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $grouped,
    public ?bool $thread,
    public mixed $channel,
    public int $id,
    ) {
    }
}
