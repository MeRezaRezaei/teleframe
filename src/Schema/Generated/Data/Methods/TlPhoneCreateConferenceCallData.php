<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.createConferenceCall (crc32 7d0444bb), returns Updates. */
final class TlPhoneCreateConferenceCallData extends Data
{
    public const METHOD = 'phone.createConferenceCall';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $muted,
    public ?bool $videoStopped,
    public ?bool $join,
    public int $randomId,
    public ?string $publicKey,
    public ?string $block,
    public mixed $params,
    ) {
    }
}
