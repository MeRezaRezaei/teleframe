<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for secureValue of SecureValue.
 *
 * bytes params carried as base64 strings: hash
 */
final class SecureValueData extends TlSecureValueAbstractData
{
    public function __construct(
    public int $flags,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSecureValueTypeAbstractData $type,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSecureDataAbstractData $data,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSecureFileAbstractData $frontSide,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSecureFileAbstractData $reverseSide,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSecureFileAbstractData $selfie,
    public ?array $translation,
    public ?array $files,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSecurePlainDataAbstractData $plainData,
    public string $hash,
    ) {
    }
}
