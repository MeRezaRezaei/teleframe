<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for decryptedMessageActionAbortKey of DecryptedMessageAction.
 */
final class DecryptedMessageActionAbortKeyData extends TlDecryptedMessageActionAbstractData
{
    public function __construct(
    public int $exchangeId,
    ) {
    }
}
