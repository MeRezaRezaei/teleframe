<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInfoBotInfoCommands;

/** Constructor model for botInfo of BotInfo (crc32 4d8a0299). */
final class TlBotInfoBotInfo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_bot_info_bot_info';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'has_preview_medias' => 'bool',
        'user_id' => 'int',
        'description' => 'string',
        'description_photo' => 'string',
        'description_document' => 'string',
        'menu_button' => 'string',
        'privacy_policy_url' => 'string',
        'app_settings' => 'string',
        'verifier_settings' => 'string',
    ];

    public function commands(): HasMany
    {
        return $this->tlChild(TlBotInfoBotInfoCommands::class);
    }
}
