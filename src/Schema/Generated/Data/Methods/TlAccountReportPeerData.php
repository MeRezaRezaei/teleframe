<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.reportPeer (crc32 c5ba3d86), returns Bool. */
final class TlAccountReportPeerData extends Data
{
    public const METHOD = 'account.reportPeer';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public mixed $reason,
    public string $message,
    ) {
    }
}
