<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 child of tf_messages holding the rich_message part (rtl + part flags).
 * Its nested blocks / text children are deferred, not modelled here.
 */
final class TfMessageRichMessage extends TfChildModel
{
    use PinsDeclaredForeignKeyLegs;

    use AccountScoped;

    protected $table = 'tf_messages_rich_message';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'rtl' => 'boolean',
        'part' => 'boolean',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(TfMessage::class, 'id', 'id');
    }
}