<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputSecureFileUploaded of InputSecureFile (crc32 3334b0f0). */
final class TlInputSecureFileInputSecureFileUploaded extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_secure_file_input_secure_file_uploaded';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_id' => 'int',
        'parts' => 'int',
        'md5_checksum' => 'string',
        'file_hash' => 'string',
        'secret' => 'string',
    ];
}
