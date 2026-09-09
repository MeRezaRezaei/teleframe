<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for fileLocation of FileLocation (crc32 53d69076). */
final class TlFileLocationFileLocation extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_file_location_file_location';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'dc_id' => 'int',
        'volume_id' => 'int',
        'local_id' => 'int',
        'secret' => 'int',
    ];
}
