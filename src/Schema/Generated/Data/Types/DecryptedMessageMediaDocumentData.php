<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for decryptedMessageMediaDocument of DecryptedMessageMedia.
 *
 * bytes params carried as base64 strings: thumb, key, iv
 */
final class DecryptedMessageMediaDocumentData extends TlDecryptedMessageMediaAbstractData
{
    public function __construct(
    public string $thumb,
    public int $thumbW,
    public int $thumbH,
    public string $mimeType,
    public int $size,
    public string $key,
    public string $iv,
    public array $attributes,
    public string $caption,
    ) {
    }
}
