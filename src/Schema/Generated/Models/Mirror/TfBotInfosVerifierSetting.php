<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfBotInfosVerifierSetting extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_bot_infos_verifier_settings';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'bot_info_id' => 'integer',
        'can_modify_custom_description' => 'boolean',
        'icon' => 'integer',
    ];
}
