<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.requestFirebaseSms (crc32 8e39261e), returns Bool. */
final class TlAuthRequestFirebaseSmsData extends Data
{
    public const METHOD = 'auth.requestFirebaseSms';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public string $phoneNumber,
    public string $phoneCodeHash,
    public ?string $safetyNetToken,
    public ?string $playIntegrityToken,
    public ?string $iosPushSecret,
    ) {
    }
}
