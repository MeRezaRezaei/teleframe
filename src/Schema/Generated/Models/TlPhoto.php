<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Domain model for photos (TL types: Photo). */
final class TlPhoto extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tf_photos';

    protected $guarded = [];
}
