<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageMediaContact of MessageMedia.
 */
final class MessageMediaContactData extends TlMessageMediaAbstractData
{
    public function __construct(
    public string $phoneNumber,
    public string $firstName,
    public string $lastName,
    public string $vcard,
    public int $userId,
    ) {
    }
}
