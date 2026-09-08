<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for cdnPublicKey of CdnPublicKey.
 */
final class CdnPublicKeyData extends TlCdnPublicKeyAbstractData
{
    public function __construct(
    public int $dcId,
    public string $publicKey,
    ) {
    }
}
