<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for topPeerCategoryBotsInline of TopPeerCategory (crc32 148677e2). */
final class TlTopPeerCategoryTopPeerCategoryBotsInline extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_top_peer_category_top_peer_category_bots_inline';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
