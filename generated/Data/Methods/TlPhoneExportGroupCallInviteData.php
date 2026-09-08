<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.exportGroupCallInvite (crc32 e6aa647f), returns phone.ExportedGroupCallInvite. */
final class TlPhoneExportGroupCallInviteData extends Data
{
    public const METHOD = 'phone.exportGroupCallInvite';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $canSelfUnmute,
    public mixed $call,
    ) {
    }
}
