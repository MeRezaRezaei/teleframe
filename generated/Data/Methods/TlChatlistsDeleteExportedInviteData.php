<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method chatlists.deleteExportedInvite (crc32 719c5c5e), returns Bool. */
final class TlChatlistsDeleteExportedInviteData extends Data
{
    public const METHOD = 'chatlists.deleteExportedInvite';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $chatlist,
    public string $slug,
    ) {
    }
}
