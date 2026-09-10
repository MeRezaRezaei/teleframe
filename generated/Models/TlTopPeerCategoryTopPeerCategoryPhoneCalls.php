<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for topPeerCategoryPhoneCalls of TopPeerCategory (crc32 1e76a78c). */
final class TlTopPeerCategoryTopPeerCategoryPhoneCalls extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_top_peer_category_top_peer_category_phone_calls';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
