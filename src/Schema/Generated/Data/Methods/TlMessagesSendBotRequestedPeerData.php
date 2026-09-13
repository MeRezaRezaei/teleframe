<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.sendBotRequestedPeer (crc32 6c5cf2a7), returns Updates. */
final class TlMessagesSendBotRequestedPeerData extends Data
{
    public const METHOD = 'messages.sendBotRequestedPeer';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public ?int $msgId,
    public ?string $webappReqId,
    public int $buttonId,
    public array $requestedPeers,
    ) {
    }
}
