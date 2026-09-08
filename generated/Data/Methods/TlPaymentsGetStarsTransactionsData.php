<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getStarsTransactions (crc32 69da4557), returns payments.StarsStatus. */
final class TlPaymentsGetStarsTransactionsData extends Data
{
    public const METHOD = 'payments.getStarsTransactions';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $inbound,
    public ?bool $outbound,
    public ?bool $ascending,
    public ?bool $ton,
    public ?string $subscriptionId,
    public mixed $peer,
    public string $offset,
    public int $limit,
    ) {
    }
}
