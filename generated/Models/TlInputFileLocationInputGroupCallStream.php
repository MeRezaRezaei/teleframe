<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputGroupCall;

/** Constructor model for inputGroupCallStream of InputFileLocation (crc32 0598a92a). */
final class TlInputFileLocationInputGroupCallStream extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_file_location_input_group_call_stream';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'time_ms' => 'int',
        'scale' => 'int',
        'video_channel' => 'int',
        'video_quality' => 'int',
    ];

    public function call(): BelongsTo
    {
        return $this->belongsTo(TlInputGroupCall::class, 'call');
    }
}
