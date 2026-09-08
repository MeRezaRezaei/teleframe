<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCallParticipantVideoGroupCallPa2d621b7be2a1Source_groups;

/** Constructor model for groupCallParticipantVideo of GroupCallParticipantVideo (crc32 67753ac8). */
final class TlGroupCallParticipantVideoGroupCallParticipantVideo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_group_call_participant_video_group_call_pa_2d621b7be2a1';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'paused' => 'bool',
        'endpoint' => 'string',
        'audio_source' => 'int',
    ];

    public function sourceGroups(): HasMany
    {
        return $this->tlChild(TlGroupCallParticipantVideoGroupCallPa2d621b7be2a1Source_groups::class);
    }
}
