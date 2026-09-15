<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 verifier_settings child — single-ctor botVerifierSettings.
 */
final class TfBotInfoVerifierSettings extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_bot_infos_verifier_settings';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'icon' => 'int',
        'can_modify_custom_description' => 'bool',
    ];

    public function botInfo(): BelongsTo
    {
        return $this->belongsTo(TfBotInfo::class, 'id', 'id');
    }
}
