<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfMessageServiceReactions extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_messages_service_reactions';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'min' => 'boolean',
        'can_see_list' => 'boolean',
        'reactions_as_tags' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(TfMessageService::class, 'id', 'id');
    }
}
