<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.reportProfilePhoto (crc32 fa8cc6f5), returns Bool. */
final class TlAccountReportProfilePhotoData extends Data
{
    public const METHOD = 'account.reportProfilePhoto';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public mixed $photoId,
    public mixed $reason,
    public string $message,
    ) {
    }
}
