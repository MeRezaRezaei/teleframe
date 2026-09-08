<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getSavedStarGifts (crc32 a319e569), returns payments.SavedStarGifts. */
final class TlPaymentsGetSavedStarGiftsData extends Data
{
    public const METHOD = 'payments.getSavedStarGifts';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $excludeUnsaved,
    public ?bool $excludeSaved,
    public ?bool $excludeUnlimited,
    public ?bool $excludeUnique,
    public ?bool $sortByValue,
    public ?bool $excludeUpgradable,
    public ?bool $excludeUnupgradable,
    public ?bool $peerColorAvailable,
    public ?bool $excludeHosted,
    public mixed $peer,
    public ?int $collectionId,
    public string $offset,
    public int $limit,
    ) {
    }
}
