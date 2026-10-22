<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.requestEncryption (crc32 f64daf43), returns EncryptedChat. */
final class TlMessagesRequestEncryptionData extends Data
{
    public const METHOD = 'messages.requestEncryption';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $userId,
    public int $randomId,
    public string $gA,
    ) {
    }
}
