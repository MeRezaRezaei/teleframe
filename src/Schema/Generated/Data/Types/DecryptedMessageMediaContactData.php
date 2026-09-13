<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for decryptedMessageMediaContact of DecryptedMessageMedia.
 */
final class DecryptedMessageMediaContactData extends TlDecryptedMessageMediaAbstractData
{
    public function __construct(
    public string $phoneNumber,
    public string $firstName,
    public string $lastName,
    public int $userId,
    ) {
    }
}
