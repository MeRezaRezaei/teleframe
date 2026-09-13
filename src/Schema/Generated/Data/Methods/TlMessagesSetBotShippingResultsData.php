<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.setBotShippingResults (crc32 e5f672fa), returns Bool. */
final class TlMessagesSetBotShippingResultsData extends Data
{
    public const METHOD = 'messages.setBotShippingResults';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public int $queryId,
    public ?string $error,
    public ?array $shippingOptions,
    ) {
    }
}
