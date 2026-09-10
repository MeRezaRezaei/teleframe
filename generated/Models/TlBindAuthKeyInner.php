<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type BindAuthKeyInner (spec §4.1). */
final class TlBindAuthKeyInner extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_bind_auth_key_inner_bind_auth_key_inner';

    protected $guarded = [];
}
