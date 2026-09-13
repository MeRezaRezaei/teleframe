<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.updateDialogFilter (crc32 1ad4a04a), returns Bool. */
final class TlMessagesUpdateDialogFilterData extends Data
{
    public const METHOD = 'messages.updateDialogFilter';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public int $id,
    public mixed $filter,
    ) {
    }
}
