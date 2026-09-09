<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type messages.PreparedInlineMessage (spec §4.1). */
final class TlMessagesPreparedInlineMessage extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_prepared_inline_message';

    protected $guarded = [];
}
