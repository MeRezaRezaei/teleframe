<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for topPeerCategoryBotsApp of TopPeerCategory (crc32 fd9e7bec). */
final class TlTopPeerCategoryTopPeerCategoryBotsApp extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_top_peer_category_top_peer_category_bots_app';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
