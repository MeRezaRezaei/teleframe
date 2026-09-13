<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method chatlists.getExportedInvites (crc32 ce03da83), returns chatlists.ExportedInvites. */
final class TlChatlistsGetExportedInvitesData extends Data
{
    public const METHOD = 'chatlists.getExportedInvites';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $chatlist,
    ) {
    }
}
