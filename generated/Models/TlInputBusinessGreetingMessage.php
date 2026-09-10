<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type InputBusinessGreetingMessage (spec §4.1). */
final class TlInputBusinessGreetingMessage extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_business_greeting_message_input_busi_21ffd04e8009';

    protected $guarded = [];
}
