<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.updateStarGiftCollection (crc32 4fddbee7), returns StarGiftCollection. */
final class TlPaymentsUpdateStarGiftCollectionData extends Data
{
    public const METHOD = 'payments.updateStarGiftCollection';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public int $collectionId,
    public ?string $title,
    public ?array $deleteStargift,
    public ?array $addStargift,
    public ?array $order,
    ) {
    }
}
