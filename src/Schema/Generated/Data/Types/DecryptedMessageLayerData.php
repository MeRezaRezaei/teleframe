<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for decryptedMessageLayer of DecryptedMessageLayer.
 *
 * bytes params carried as base64 strings: random_bytes
 */
final class DecryptedMessageLayerData extends TlDecryptedMessageLayerAbstractData
{
    public function __construct(
    public string $randomBytes,
    public int $layer,
    public int $inSeqNo,
    public int $outSeqNo,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDecryptedMessageAbstractData $message,
    ) {
    }
}
