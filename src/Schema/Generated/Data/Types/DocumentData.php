<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for document of Document.
 *
 * bytes params carried as base64 strings: file_reference
 */
final class DocumentData extends TlDocumentAbstractData
{
    public function __construct(
    public int $flags,
    public int $id,
    public int $accessHash,
    public string $fileReference,
    public int $date,
    public string $mimeType,
    public int $size,
    public ?array $thumbs,
    public ?array $videoThumbs,
    public int $dcId,
    public array $attributes,
    ) {
    }
}
