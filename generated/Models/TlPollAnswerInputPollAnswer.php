<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMedia;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTextWithEntities;

/** Constructor model for inputPollAnswer of PollAnswer (crc32 199fed96). */
final class TlPollAnswerInputPollAnswer extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_poll_answer_input_poll_answer';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
    ];

    public function text(): BelongsTo
    {
        return $this->belongsTo(TlTextWithEntities::class, 'text');
    }
    public function media(): BelongsTo
    {
        return $this->belongsTo(TlInputMedia::class, 'media');
    }
}
