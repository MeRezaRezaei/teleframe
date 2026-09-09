<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInfoBotInfo;

/** Anchor model for TL type BotVerifierSettings (spec §4.1). */
final class TlBotVerifierSettings extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_bot_verifier_settings';

    protected $guarded = [];

    public function verifierSettings(): HasMany
    {
        return $this->hasMany(TlBotInfoBotInfo::class, 'verifier_settings');
    }
}
