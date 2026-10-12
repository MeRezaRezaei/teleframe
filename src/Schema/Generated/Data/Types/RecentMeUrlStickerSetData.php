<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for recentMeUrlStickerSet of RecentMeUrl.
 */
final class RecentMeUrlStickerSetData extends TlRecentMeUrlAbstractData
{
    public function __construct(
    public string $url,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStickerSetCoveredAbstractData $set,
    ) {
    }
}
