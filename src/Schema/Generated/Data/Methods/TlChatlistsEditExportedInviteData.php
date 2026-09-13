<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method chatlists.editExportedInvite (crc32 653db63d), returns ExportedChatlistInvite. */
final class TlChatlistsEditExportedInviteData extends Data
{
    public const METHOD = 'chatlists.editExportedInvite';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $chatlist,
    public string $slug,
    public ?string $title,
    public ?array $peers,
    ) {
    }
}
