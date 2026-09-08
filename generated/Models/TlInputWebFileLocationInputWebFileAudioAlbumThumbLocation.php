<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputWebFileAudioAlbumThumbLocation of InputWebFileLocation (crc32 f46fe924). */
final class TlInputWebFileLocationInputWebFileAudioAlbumThumbLocation extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_web_file_location_input_web_file_aud_36d962fc9d91';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'small' => 'bool',
        'document' => 'string',
        'title' => 'string',
        'performer' => 'string',
    ];
}
