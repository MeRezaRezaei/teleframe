<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.requestCall (crc32 42ff96ed), returns phone.PhoneCall. */
final class TlPhoneRequestCallData extends Data
{
    public const METHOD = 'phone.requestCall';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $video,
    public mixed $userId,
    public int $randomId,
    public string $gAHash,
    public mixed $protocol,
    ) {
    }
}
