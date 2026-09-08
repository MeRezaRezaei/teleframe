<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for groupCallMessage of GroupCallMessage (crc32 1a8afc7e). */
final class TlGroupCallMessageGroupCallMessage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_group_call_message_group_call_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'from_admin' => 'bool',
        'tl_id' => 'int',
        'from_id' => 'string',
        'date' => 'int',
        'message' => 'string',
        'paid_message_stars' => 'int',
    ];
}
