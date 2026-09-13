<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.toggleStickerSets (crc32 b5052fea), returns Bool. */
final class TlMessagesToggleStickerSetsData extends Data
{
    public const METHOD = 'messages.toggleStickerSets';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $uninstall,
    public ?bool $archive,
    public ?bool $unarchive,
    public array $stickersets,
    ) {
    }
}
