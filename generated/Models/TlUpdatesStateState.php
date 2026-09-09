<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for updates.state of updates.State (crc32 a56c2a3e). */
final class TlUpdatesStateState extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_updates_state_state';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'pts' => 'int',
        'qts' => 'int',
        'date' => 'int',
        'seq' => 'int',
        'unread_count' => 'int',
    ];
}
