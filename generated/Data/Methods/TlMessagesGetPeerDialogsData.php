<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getPeerDialogs (crc32 e470bcfd), returns messages.PeerDialogs. */
final class TlMessagesGetPeerDialogsData extends Data
{
    public const METHOD = 'messages.getPeerDialogs';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public array $peers,
    ) {
    }
}
