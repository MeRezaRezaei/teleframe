<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for webDocumentNoProxy of WebDocument.
 */
final class WebDocumentNoProxyData extends TlWebDocumentAbstractData
{
    public function __construct(
    public string $url,
    public int $size,
    public string $mimeType,
    public array $attributes,
    ) {
    }
}
