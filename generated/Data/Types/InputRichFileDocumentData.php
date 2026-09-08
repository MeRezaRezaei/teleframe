<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputRichFileDocument of InputRichFile.
 */
final class InputRichFileDocumentData extends TlInputRichFileAbstractData
{
    public function __construct(
    public string $id,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputDocumentAbstractData $document,
    ) {
    }
}
