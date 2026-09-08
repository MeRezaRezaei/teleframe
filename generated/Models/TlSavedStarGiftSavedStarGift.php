<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSavedStarGiftSavedStarGiftCollection_id;

/** Constructor model for savedStarGift of SavedStarGift (crc32 41df43fc). */
final class TlSavedStarGiftSavedStarGift extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_saved_star_gift_saved_star_gift';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'name_hidden' => 'bool',
        'unsaved' => 'bool',
        'refunded' => 'bool',
        'can_upgrade' => 'bool',
        'pinned_to_top' => 'bool',
        'upgrade_separate' => 'bool',
        'from_id' => 'string',
        'date' => 'int',
        'gift' => 'string',
        'message' => 'string',
        'msg_id' => 'int',
        'saved_id' => 'int',
        'convert_stars' => 'int',
        'upgrade_stars' => 'int',
        'can_export_at' => 'int',
        'transfer_stars' => 'int',
        'can_transfer_at' => 'int',
        'can_resell_at' => 'int',
        'prepaid_upgrade_hash' => 'string',
        'drop_original_details_stars' => 'int',
        'gift_num' => 'int',
        'can_craft_at' => 'int',
    ];

    public function collectionId(): HasMany
    {
        return $this->tlChild(TlSavedStarGiftSavedStarGiftCollection_id::class);
    }
}
