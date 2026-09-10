<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTextWithEntities;

/** Constructor model for messages.composedMessageWithAI of messages.ComposedMessageWithAI (crc32 90d7adfa). */
final class TlMessagesComposedMessageWithAIComposedMessageWithAI extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_composed_message_with_a_i_compose_55280cfdf5bd';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
    ];

    public function resultText(): BelongsTo
    {
        return $this->belongsTo(TlTextWithEntities::class, 'result_text');
    }
    public function diffText(): BelongsTo
    {
        return $this->belongsTo(TlTextWithEntities::class, 'diff_text');
    }
}
