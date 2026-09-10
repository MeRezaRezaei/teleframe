<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesAvailableEffectsAvailableEffectsDocuments;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesAvailableEffectsAvailableEffectsEffects;

/** Constructor model for messages.availableEffects of messages.AvailableEffects (crc32 bddb616e). */
final class TlMessagesAvailableEffectsAvailableEffects extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_available_effects_available_effects';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'hash' => 'int',
    ];

    public function effects(): HasMany
    {
        return $this->tlChild(TlMessagesAvailableEffectsAvailableEffectsEffects::class);
    }
    public function documents(): HasMany
    {
        return $this->tlChild(TlMessagesAvailableEffectsAvailableEffectsDocuments::class);
    }
}
