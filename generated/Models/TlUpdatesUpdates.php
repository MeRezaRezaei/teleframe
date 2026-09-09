<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesUpdatesChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesUpdatesUpdates;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesUpdatesUsers;

/** Constructor model for updates of Updates (crc32 74ae4240). */
final class TlUpdatesUpdates extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_updates_updates';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'date' => 'int',
        'seq' => 'int',
    ];

    public function updates(): HasMany
    {
        return $this->tlChild(TlUpdatesUpdatesUpdates::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlUpdatesUpdatesUsers::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlUpdatesUpdatesChats::class);
    }
}
