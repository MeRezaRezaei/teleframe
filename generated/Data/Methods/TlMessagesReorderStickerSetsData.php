<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.reorderStickerSets (crc32 78337739), returns Bool. */
final class TlMessagesReorderStickerSetsData extends Data
{
    public const METHOD = 'messages.reorderStickerSets';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $masks,
    public ?bool $emojis,
    public array $order,
    ) {
    }
}
