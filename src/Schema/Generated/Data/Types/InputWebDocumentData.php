<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputWebDocument of InputWebDocument.
 */
final class InputWebDocumentData extends TlInputWebDocumentAbstractData
{
    public function __construct(
    public string $url,
    public int $size,
    public string $mimeType,
    public array $attributes,
    ) {
    }
}
