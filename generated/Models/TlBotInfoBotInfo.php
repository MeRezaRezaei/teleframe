<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotAppSettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInfoBotInfoCommands;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotMenuButton;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotVerifierSettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoto;

/** Constructor model for botInfo of BotInfo (crc32 4d8a0299). */
final class TlBotInfoBotInfo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_bot_info_bot_info';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'has_preview_medias' => 'bool',
        'user_id' => 'int',
        'description' => 'string',
        'privacy_policy_url' => 'string',
    ];

    public function commands(): HasMany
    {
        return $this->tlChild(TlBotInfoBotInfoCommands::class);
    }

    public function descriptionPhoto(): BelongsTo
    {
        return $this->belongsTo(TlPhoto::class, 'description_photo');
    }
    public function descriptionDocument(): BelongsTo
    {
        return $this->belongsTo(TlDocument::class, 'description_document');
    }
    public function menuButton(): BelongsTo
    {
        return $this->belongsTo(TlBotMenuButton::class, 'menu_button');
    }
    public function appSettings(): BelongsTo
    {
        return $this->belongsTo(TlBotAppSettings::class, 'app_settings');
    }
    public function verifierSettings(): BelongsTo
    {
        return $this->belongsTo(TlBotVerifierSettings::class, 'verifier_settings');
    }
}
