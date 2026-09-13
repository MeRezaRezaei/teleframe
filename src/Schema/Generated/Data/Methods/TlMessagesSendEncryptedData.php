<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.sendEncrypted (crc32 44fa7a15), returns messages.SentEncryptedMessage. */
final class TlMessagesSendEncryptedData extends Data
{
    public const METHOD = 'messages.sendEncrypted';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $silent,
    public mixed $peer,
    public int $randomId,
    public string $data,
    ) {
    }
}
