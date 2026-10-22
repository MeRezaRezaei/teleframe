<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.recentStickers of messages.RecentStickers.
 */
final class TlMessagesRecentStickersData extends TlMessagesRecentStickersAbstractData
{
    public function __construct(
    public int $hash,
    public array $packs,
    public array $stickers,
    public array $dates,
    ) {
    }
}
