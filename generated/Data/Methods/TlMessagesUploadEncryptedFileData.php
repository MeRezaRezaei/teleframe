<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.uploadEncryptedFile (crc32 5057c497), returns EncryptedFile. */
final class TlMessagesUploadEncryptedFileData extends Data
{
    public const METHOD = 'messages.uploadEncryptedFile';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public mixed $file,
    ) {
    }
}
