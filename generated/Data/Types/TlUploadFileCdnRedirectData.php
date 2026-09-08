<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for upload.fileCdnRedirect of upload.File.
 *
 * bytes params carried as base64 strings: file_token, encryption_key, encryption_iv
 */
final class TlUploadFileCdnRedirectData extends TlUploadFileAbstractData
{
    public function __construct(
    public int $dcId,
    public string $fileToken,
    public string $encryptionKey,
    public string $encryptionIv,
    public array $fileHashes,
    ) {
    }
}
