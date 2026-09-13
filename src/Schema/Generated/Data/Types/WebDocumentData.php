<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for webDocument of WebDocument.
 */
final class WebDocumentData extends TlWebDocumentAbstractData
{
    public function __construct(
    public string $url,
    public int $accessHash,
    public int $size,
    public string $mimeType,
    public array $attributes,
    ) {
    }
}
