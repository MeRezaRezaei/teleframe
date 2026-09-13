<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for decryptedMessageMediaExternalDocument of DecryptedMessageMedia.
 */
final class DecryptedMessageMediaExternalDocumentData extends TlDecryptedMessageMediaAbstractData
{
    public function __construct(
    public int $id,
    public int $accessHash,
    public int $date,
    public string $mimeType,
    public int $size,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPhotoSizeAbstractData $thumb,
    public int $dcId,
    public array $attributes,
    ) {
    }
}
