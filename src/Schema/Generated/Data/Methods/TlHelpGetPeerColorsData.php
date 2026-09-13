<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method help.getPeerColors (crc32 da80f42f), returns help.PeerColors. */
final class TlHelpGetPeerColorsData extends Data
{
    public const METHOD = 'help.getPeerColors';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
