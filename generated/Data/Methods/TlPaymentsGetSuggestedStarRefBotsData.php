<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getSuggestedStarRefBots (crc32 0d6b48f7), returns payments.SuggestedStarRefBots. */
final class TlPaymentsGetSuggestedStarRefBotsData extends Data
{
    public const METHOD = 'payments.getSuggestedStarRefBots';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $orderByRevenue,
    public ?bool $orderByDate,
    public mixed $peer,
    public string $offset,
    public int $limit,
    ) {
    }
}
