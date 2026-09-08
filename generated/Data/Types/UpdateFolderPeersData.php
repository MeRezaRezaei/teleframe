<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateFolderPeers of Update.
 */
final class UpdateFolderPeersData extends TlUpdateAbstractData
{
    public function __construct(
    public array $folderPeers,
    public int $pts,
    public int $ptsCount,
    ) {
    }
}
