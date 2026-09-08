<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for encryptedMessageService of EncryptedMessage.
 *
 * bytes params carried as base64 strings: bytes
 */
final class EncryptedMessageServiceData extends TlEncryptedMessageAbstractData
{
    public function __construct(
    public int $randomId,
    public int $chatId,
    public int $date,
    public string $bytes,
    ) {
    }
}
