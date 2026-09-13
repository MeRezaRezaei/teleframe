<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.discardEncryption (crc32 f393aea0), returns Bool. */
final class TlMessagesDiscardEncryptionData extends Data
{
    public const METHOD = 'messages.discardEncryption';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $deleteHistory,
    public int $chatId,
    ) {
    }
}
