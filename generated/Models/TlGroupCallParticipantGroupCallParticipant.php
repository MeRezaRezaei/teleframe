<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCallParticipantVideo;

/** Constructor model for groupCallParticipant of GroupCallParticipant (crc32 2a3dc7ac). */
final class TlGroupCallParticipantGroupCallParticipant extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_group_call_participant_group_call_participant';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'muted' => 'bool',
        'left' => 'bool',
        'can_self_unmute' => 'bool',
        'just_joined' => 'bool',
        'versioned' => 'bool',
        'min' => 'bool',
        'muted_by_you' => 'bool',
        'volume_by_admin' => 'bool',
        'self' => 'bool',
        'video_joined' => 'bool',
        'date' => 'int',
        'active_date' => 'int',
        'source' => 'int',
        'volume' => 'int',
        'about' => 'string',
        'raise_hand_rating' => 'int',
        'paid_stars_total' => 'int',
    ];

    public function video(): BelongsTo
    {
        return $this->belongsTo(TlGroupCallParticipantVideo::class, 'video');
    }
    public function presentation(): BelongsTo
    {
        return $this->belongsTo(TlGroupCallParticipantVideo::class, 'presentation');
    }
}
