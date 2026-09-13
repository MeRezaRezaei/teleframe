<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Domain model for chats (TL types: Chat). */
final class TlChat extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tf_chats';

    protected $guarded = [];
}
