<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for botVerifierSettings of BotVerifierSettings (crc32 b0cd6617). */
final class TlBotVerifierSettingsBotVerifierSettings extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_bot_verifier_settings_bot_verifier_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'can_modify_custom_description' => 'bool',
        'icon' => 'int',
        'company' => 'string',
        'custom_description' => 'string',
    ];
}
