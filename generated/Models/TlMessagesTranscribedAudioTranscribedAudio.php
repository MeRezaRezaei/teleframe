<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for messages.transcribedAudio of messages.TranscribedAudio (crc32 cfb9d957). */
final class TlMessagesTranscribedAudioTranscribedAudio extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_transcribed_audio_transcribed_audio';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'pending' => 'bool',
        'transcription_id' => 'int',
        'text' => 'string',
        'trial_remains_num' => 'int',
        'trial_remains_until_date' => 'int',
    ];
}
