<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Domain model for channels (TL types: Chat). */
final class TlChannel extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tf_channels';

    protected $guarded = [];
}
