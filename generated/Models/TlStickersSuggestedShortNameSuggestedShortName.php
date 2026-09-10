<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for stickers.suggestedShortName of stickers.SuggestedShortName (crc32 85fea03f). */
final class TlStickersSuggestedShortNameSuggestedShortName extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_stickers_suggested_short_name_suggested_short_name';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'short_name' => 'string',
    ];
}
