<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for inputFileLocation of InputFileLocation (crc32 dfdaabe1). */
final class TlInputFileLocationInputFileLocation extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_file_location_input_file_location';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'volume_id' => 'int',
        'local_id' => 'int',
        'secret' => 'int',
        'file_reference' => 'string',
    ];
}
