<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.updateColor (crc32 684d214e), returns Bool. */
final class TlAccountUpdateColorData extends Data
{
    public const METHOD = 'account.updateColor';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $forProfile,
    public mixed $color,
    ) {
    }
}
