<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for secureValueErrorTranslationFiles of SecureValueError.
 */
final class SecureValueErrorTranslationFilesData extends TlSecureValueErrorAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSecureValueTypeAbstractData $type,
    public array $fileHash,
    public string $text,
    ) {
    }
}
