<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type WebPageAttribute (spec §4.1). */
final class TlWebPageAttribute extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_web_page_attribute_web_page_attribute_ai_compose_tone';

    protected $guarded = [];
}
