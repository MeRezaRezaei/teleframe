<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.checkUrlAuthMatchCode (crc32 c9a47b0b), returns Bool. */
final class TlMessagesCheckUrlAuthMatchCodeData extends Data
{
    public const METHOD = 'messages.checkUrlAuthMatchCode';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $url,
    public string $matchCode,
    ) {
    }
}
