<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.sendGroupCallEncryptedMessage (crc32 e5afa56d), returns Bool. */
final class TlPhoneSendGroupCallEncryptedMessageData extends Data
{
    public const METHOD = 'phone.sendGroupCallEncryptedMessage';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $call,
    public string $encryptedMessage,
    ) {
    }
}
