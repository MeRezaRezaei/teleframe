<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputChannel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaCoordinates;

/** Constructor model for inputMediaAreaChannelPost of MediaArea (crc32 2271f2bf). */
final class TlMediaAreaInputMediaAreaChannelPost extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_media_area_input_media_area_channel_post';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'msg_id' => 'int',
    ];

    public function coordinates(): BelongsTo
    {
        return $this->belongsTo(TlMediaAreaCoordinates::class, 'coordinates');
    }
    public function channel(): BelongsTo
    {
        return $this->belongsTo(TlInputChannel::class, 'channel');
    }
}
