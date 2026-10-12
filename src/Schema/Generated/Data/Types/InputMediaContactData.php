<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputMediaContact of InputMedia.
 */
final class InputMediaContactData extends TlInputMediaAbstractData
{
    public function __construct(
    public string $phoneNumber,
    public string $firstName,
    public string $lastName,
    public string $vcard,
    ) {
    }
}
