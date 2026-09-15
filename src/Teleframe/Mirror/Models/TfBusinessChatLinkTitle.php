<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfBusinessChatLinkTitle extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_business_chat_links_title';

    protected $primaryKey = 'link';

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [];

    public function chatLink(): BelongsTo
    {
        return $this->belongsTo(TfBusinessChatLink::class, 'link', 'link');
    }
}
