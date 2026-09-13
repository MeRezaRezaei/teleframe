<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.reportMusicListen (crc32 ddbcd819), returns Bool. */
final class TlMessagesReportMusicListenData extends Data
{
    public const METHOD = 'messages.reportMusicListen';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $id,
    public int $listenedDuration,
    ) {
    }
}
