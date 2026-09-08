<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for suggestedPost of SuggestedPost (crc32 0e8e37e5). */
final class TlSuggestedPostSuggestedPost extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_suggested_post_suggested_post';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'accepted' => 'bool',
        'rejected' => 'bool',
        'price' => 'string',
        'schedule_date' => 'int',
    ];
}
