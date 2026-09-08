<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputReplyToMonoForum of InputReplyTo (crc32 69d66c45). */
final class TlInputReplyToInputReplyToMonoForum extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_reply_to_input_reply_to_mono_forum';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'monoforum_peer_id' => 'string',
    ];
}
