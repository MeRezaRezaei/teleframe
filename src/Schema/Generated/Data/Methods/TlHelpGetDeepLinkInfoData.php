<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method help.getDeepLinkInfo (crc32 3fedc75f), returns help.DeepLinkInfo. */
final class TlHelpGetDeepLinkInfoData extends Data
{
    public const METHOD = 'help.getDeepLinkInfo';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $path,
    ) {
    }
}
