<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for mediaAreaWeather of MediaArea (crc32 49a6549c). */
final class TlMediaAreaMediaAreaWeather extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_media_area_media_area_weather';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'coordinates' => 'string',
        'emoji' => 'string',
        'temperature_c' => 'float',
        'color' => 'int',
    ];
}
