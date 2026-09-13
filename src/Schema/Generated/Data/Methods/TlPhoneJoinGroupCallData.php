<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.joinGroupCall (crc32 8fb53057), returns Updates. */
final class TlPhoneJoinGroupCallData extends Data
{
    public const METHOD = 'phone.joinGroupCall';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $muted,
    public ?bool $videoStopped,
    public mixed $call,
    public mixed $joinAs,
    public ?string $inviteHash,
    public ?string $publicKey,
    public ?string $block,
    public mixed $params,
    ) {
    }
}
