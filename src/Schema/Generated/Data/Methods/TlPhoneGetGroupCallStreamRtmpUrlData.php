<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.getGroupCallStreamRtmpUrl (crc32 5af4c73a), returns phone.GroupCallStreamRtmpUrl. */
final class TlPhoneGetGroupCallStreamRtmpUrlData extends Data
{
    public const METHOD = 'phone.getGroupCallStreamRtmpUrl';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $liveStory,
    public mixed $peer,
    public mixed $revoke,
    ) {
    }
}
