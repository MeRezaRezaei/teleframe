<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.registerDevice (crc32 ec86017a), returns Bool. */
final class TlAccountRegisterDeviceData extends Data
{
    public const METHOD = 'account.registerDevice';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $noMuted,
    public int $tokenType,
    public string $token,
    public mixed $appSandbox,
    public string $secret,
    public array $otherUids,
    ) {
    }
}
