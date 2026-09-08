<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for rsa_public_key of RSAPublicKey.
 */
final class RsaPublicKeyData extends TlRSAPublicKeyAbstractData
{
    public function __construct(
    public string $n,
    public string $e,
    ) {
    }
}
