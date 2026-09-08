<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for businessIntro of BusinessIntro (crc32 5a0a066d). */
final class TlBusinessIntroBusinessIntro extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_business_intro_business_intro';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'title' => 'string',
        'description' => 'string',
        'sticker' => 'string',
    ];
}
