<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMedia;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPoll;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollResults;

/** Constructor model for messageMediaPoll of MessageMedia (crc32 773f4e66). */
final class TlMessageMediaMessageMediaPoll extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_media_message_media_poll';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
    ];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(TlPoll::class, 'poll');
    }
    public function results(): BelongsTo
    {
        return $this->belongsTo(TlPollResults::class, 'results');
    }
    public function attachedMedia(): BelongsTo
    {
        return $this->belongsTo(TlMessageMedia::class, 'attached_media');
    }
}
