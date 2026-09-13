<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for decryptedMessageActionResend of DecryptedMessageAction.
 */
final class DecryptedMessageActionResendData extends TlDecryptedMessageActionAbstractData
{
    public function __construct(
    public int $startSeqNo,
    public int $endSeqNo,
    ) {
    }
}
