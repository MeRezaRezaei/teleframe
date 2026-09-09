<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCallParticipantVideoSourceGroupD4c024526fb4Sources;

/** Constructor model for groupCallParticipantVideoSourceGroup of GroupCallParticipantVideoSourceGroup (crc32 dcb118b7). */
final class TlGroupCallParticipantVideoSourceGroupGroupCallParticipantVideoSourceGroup extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_group_call_participant_video_source_group__d4c024526fb4';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'semantics' => 'string',
    ];

    public function sources(): HasMany
    {
        return $this->tlChild(TlGroupCallParticipantVideoSourceGroupD4c024526fb4Sources::class);
    }
}
