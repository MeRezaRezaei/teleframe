<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBool;

/** Constructor model for updateChannelViewForumAsMessages of Update (crc32 07b68920). */
final class TlUpdateUpdateChannelViewForumAsMessages extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_channel_view_forum_as_messages';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'channel_id' => 'int',
    ];

    public function enabled(): BelongsTo
    {
        return $this->belongsTo(TlBool::class, 'enabled');
    }
}
