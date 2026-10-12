<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for decryptedMessageMediaPhoto of DecryptedMessageMedia.
 *
 * bytes params carried as base64 strings: thumb, key, iv
 */
final class DecryptedMessageMediaPhotoData extends TlDecryptedMessageMediaAbstractData
{
    public function __construct(
    public string $thumb,
    public int $thumbW,
    public int $thumbH,
    public int $w,
    public int $h,
    public int $size,
    public string $key,
    public string $iv,
    public string $caption,
    ) {
    }
}
