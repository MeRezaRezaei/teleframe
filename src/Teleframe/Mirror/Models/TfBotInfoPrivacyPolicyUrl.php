<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfBotInfoPrivacyPolicyUrl extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_bot_infos_privacy_policy_url';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [];

    public function botInfo(): BelongsTo
    {
        return $this->belongsTo(TfBotInfo::class, 'id', 'id');
    }
}
