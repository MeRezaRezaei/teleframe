<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputSecureValue of InputSecureValue.
 */
final class InputSecureValueData extends TlInputSecureValueAbstractData
{
    public function __construct(
    public int $flags,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSecureValueTypeAbstractData $type,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSecureDataAbstractData $data,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputSecureFileAbstractData $frontSide,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputSecureFileAbstractData $reverseSide,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputSecureFileAbstractData $selfie,
    public ?array $translation,
    public ?array $files,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSecurePlainDataAbstractData $plainData,
    ) {
    }
}
