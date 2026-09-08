<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputTakeoutFileLocation of InputFileLocation (crc32 29be5899). */
final class TlInputFileLocationInputTakeoutFileLocation extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_file_location_input_takeout_file_location';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
