<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateUserPhone of Update.
 */
final class UpdateUserPhoneData extends TlUpdateAbstractData
{
    public function __construct(
    public int $userId,
    public string $phone,
    ) {
    }
}
