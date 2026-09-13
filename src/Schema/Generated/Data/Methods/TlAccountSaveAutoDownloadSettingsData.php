<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.saveAutoDownloadSettings (crc32 76f36233), returns Bool. */
final class TlAccountSaveAutoDownloadSettingsData extends Data
{
    public const METHOD = 'account.saveAutoDownloadSettings';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $low,
    public ?bool $high,
    public mixed $settings,
    ) {
    }
}
