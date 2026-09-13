<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.activateStealthMode (crc32 57bbd166), returns Updates. */
final class TlStoriesActivateStealthModeData extends Data
{
    public const METHOD = 'stories.activateStealthMode';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $past,
    public ?bool $future,
    ) {
    }
}
