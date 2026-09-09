<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for messages.affectedHistory of messages.AffectedHistory (crc32 b45c69d1). */
final class TlMessagesAffectedHistoryAffectedHistory extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_affected_history_affected_history';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'pts' => 'int',
        'pts_count' => 'int',
        'tl_offset' => 'int',
    ];
}
