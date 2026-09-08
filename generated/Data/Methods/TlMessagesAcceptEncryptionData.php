<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.acceptEncryption (crc32 3dbc0415), returns EncryptedChat. */
final class TlMessagesAcceptEncryptionData extends Data
{
    public const METHOD = 'messages.acceptEncryption';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public string $gB,
    public int $keyFingerprint,
    ) {
    }
}
