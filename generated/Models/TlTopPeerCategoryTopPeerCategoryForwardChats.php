<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for topPeerCategoryForwardChats of TopPeerCategory (crc32 fbeec0f0). */
final class TlTopPeerCategoryTopPeerCategoryForwardChats extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_top_peer_category_top_peer_category_forward_chats';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
