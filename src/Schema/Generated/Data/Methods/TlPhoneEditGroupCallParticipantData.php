<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.editGroupCallParticipant (crc32 a5273abf), returns Updates. */
final class TlPhoneEditGroupCallParticipantData extends Data
{
    public const METHOD = 'phone.editGroupCallParticipant';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $call,
    public mixed $participant,
    public mixed $muted,
    public ?int $volume,
    public mixed $raiseHand,
    public mixed $videoStopped,
    public mixed $videoPaused,
    public mixed $presentationPaused,
    ) {
    }
}
