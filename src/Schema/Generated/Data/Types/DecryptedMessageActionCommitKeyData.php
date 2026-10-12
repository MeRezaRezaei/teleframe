<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for decryptedMessageActionCommitKey of DecryptedMessageAction.
 */
final class DecryptedMessageActionCommitKeyData extends TlDecryptedMessageActionAbstractData
{
    public function __construct(
    public int $exchangeId,
    public int $keyFingerprint,
    ) {
    }
}
