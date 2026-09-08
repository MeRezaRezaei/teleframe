<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.setCustomVerification (crc32 8b89dfbd), returns Bool. */
final class TlBotsSetCustomVerificationData extends Data
{
    public const METHOD = 'bots.setCustomVerification';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $enabled,
    public mixed $bot,
    public mixed $peer,
    public ?string $customDescription,
    ) {
    }
}
