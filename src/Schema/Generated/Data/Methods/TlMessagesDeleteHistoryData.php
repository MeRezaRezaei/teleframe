<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.deleteHistory (crc32 b08f922a), returns messages.AffectedHistory. */
final class TlMessagesDeleteHistoryData extends Data
{
    public const METHOD = 'messages.deleteHistory';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $justClear,
    public ?bool $revoke,
    public mixed $peer,
    public int $maxId,
    public ?int $minDate,
    public ?int $maxDate,
    ) {
    }
}
