<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for webPageAttributeAiComposeTone of WebPageAttribute (crc32 7781fe18). */
final class TlWebPageAttributeWebPageAttributeAiComposeTone extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_web_page_attribute_web_page_attribute_ai_compose_tone';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'emoji_id' => 'int',
    ];
}
