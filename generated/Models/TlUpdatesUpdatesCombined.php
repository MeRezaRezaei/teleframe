<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesUpdatesCombinedChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesUpdatesCombinedUpdates;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesUpdatesCombinedUsers;

/** Constructor model for updatesCombined of Updates (crc32 725b04c3). */
final class TlUpdatesUpdatesCombined extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_updates_updates_combined';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'date' => 'int',
        'seq_start' => 'int',
        'seq' => 'int',
    ];

    public function updates(): HasMany
    {
        return $this->tlChild(TlUpdatesUpdatesCombinedUpdates::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlUpdatesUpdatesCombinedUsers::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlUpdatesUpdatesCombinedChats::class);
    }
}
