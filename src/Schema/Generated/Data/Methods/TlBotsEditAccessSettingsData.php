<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.editAccessSettings (crc32 31813cd8), returns Bool. */
final class TlBotsEditAccessSettingsData extends Data
{
    public const METHOD = 'bots.editAccessSettings';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $restricted,
    public mixed $bot,
    public ?array $addUsers,
    ) {
    }
}
