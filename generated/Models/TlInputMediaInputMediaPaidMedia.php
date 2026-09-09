<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaPaidMediaExtended_media;

/** Constructor model for inputMediaPaidMedia of InputMedia (crc32 c4103386). */
final class TlInputMediaInputMediaPaidMedia extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_media_input_media_paid_media';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'stars_amount' => 'int',
        'payload' => 'string',
    ];

    public function extendedMedia(): HasMany
    {
        return $this->tlChild(TlInputMediaInputMediaPaidMediaExtended_media::class);
    }
}
