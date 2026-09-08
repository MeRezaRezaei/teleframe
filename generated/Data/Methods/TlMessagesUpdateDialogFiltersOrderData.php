<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.updateDialogFiltersOrder (crc32 c563c1e4), returns Bool. */
final class TlMessagesUpdateDialogFiltersOrderData extends Data
{
    public const METHOD = 'messages.updateDialogFiltersOrder';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public array $order,
    ) {
    }
}
