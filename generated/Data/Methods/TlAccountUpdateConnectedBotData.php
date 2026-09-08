<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.updateConnectedBot (crc32 66a08c7e), returns Updates. */
final class TlAccountUpdateConnectedBotData extends Data
{
    public const METHOD = 'account.updateConnectedBot';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $deleted,
    public mixed $rights,
    public mixed $bot,
    public mixed $recipients,
    ) {
    }
}
