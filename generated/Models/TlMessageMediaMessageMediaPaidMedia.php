<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaPaidMediaExtended_media;

/** Constructor model for messageMediaPaidMedia of MessageMedia (crc32 a8852491). */
final class TlMessageMediaMessageMediaPaidMedia extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_media_message_media_paid_media';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'stars_amount' => 'int',
    ];

    public function extendedMedia(): HasMany
    {
        return $this->tlChild(TlMessageMediaMessageMediaPaidMediaExtended_media::class);
    }
}
