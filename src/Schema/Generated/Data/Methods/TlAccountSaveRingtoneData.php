<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.saveRingtone (crc32 3dea5b03), returns account.SavedRingtone. */
final class TlAccountSaveRingtoneData extends Data
{
    public const METHOD = 'account.saveRingtone';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $id,
    public mixed $unsave,
    ) {
    }
}
