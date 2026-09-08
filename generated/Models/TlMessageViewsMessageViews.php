<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messageViews of MessageViews (crc32 455b853d). */
final class TlMessageViewsMessageViews extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_views_message_views';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'views' => 'int',
        'forwards' => 'int',
        'replies' => 'string',
    ];
}
