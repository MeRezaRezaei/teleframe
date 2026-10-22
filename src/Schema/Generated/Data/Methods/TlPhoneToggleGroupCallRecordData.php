<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.toggleGroupCallRecord (crc32 f128c708), returns Updates. */
final class TlPhoneToggleGroupCallRecordData extends Data
{
    public const METHOD = 'phone.toggleGroupCallRecord';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $start,
    public ?bool $video,
    public mixed $call,
    public ?string $title,
    public mixed $videoPortrait,
    ) {
    }
}
