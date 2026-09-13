<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for secureData of SecureData.
 *
 * bytes params carried as base64 strings: data, data_hash, secret
 */
final class SecureDataData extends TlSecureDataAbstractData
{
    public function __construct(
    public string $data,
    public string $dataHash,
    public string $secret,
    ) {
    }
}
