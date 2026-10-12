<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.createGroupCall (crc32 48cdc6d8), returns Updates. */
final class TlPhoneCreateGroupCallData extends Data
{
    public const METHOD = 'phone.createGroupCall';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $rtmpStream,
    public mixed $peer,
    public int $randomId,
    public ?string $title,
    public ?int $scheduleDate,
    ) {
    }
}
