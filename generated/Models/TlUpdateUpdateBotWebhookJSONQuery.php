<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDataJSON;

/** Constructor model for updateBotWebhookJSONQuery of Update (crc32 9b9240a6). */
final class TlUpdateUpdateBotWebhookJSONQuery extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_bot_webhook_j_s_o_n_query';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'query_id' => 'int',
        'timeout' => 'int',
    ];

    public function data(): BelongsTo
    {
        return $this->belongsTo(TlDataJSON::class, 'data');
    }
}
