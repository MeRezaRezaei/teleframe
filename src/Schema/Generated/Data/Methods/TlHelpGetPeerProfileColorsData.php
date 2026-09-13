<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method help.getPeerProfileColors (crc32 abcfa9fd), returns help.PeerColors. */
final class TlHelpGetPeerProfileColorsData extends Data
{
    public const METHOD = 'help.getPeerProfileColors';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
