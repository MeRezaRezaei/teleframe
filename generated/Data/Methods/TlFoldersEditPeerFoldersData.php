<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method folders.editPeerFolders (crc32 6847d0ab), returns Updates. */
final class TlFoldersEditPeerFoldersData extends Data
{
    public const METHOD = 'folders.editPeerFolders';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public array $folderPeers,
    ) {
    }
}
