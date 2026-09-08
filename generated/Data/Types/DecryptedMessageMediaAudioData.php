<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for decryptedMessageMediaAudio of DecryptedMessageMedia.
 *
 * bytes params carried as base64 strings: key, iv
 */
final class DecryptedMessageMediaAudioData extends TlDecryptedMessageMediaAbstractData
{
    public function __construct(
    public int $duration,
    public string $mimeType,
    public int $size,
    public string $key,
    public string $iv,
    ) {
    }
}
