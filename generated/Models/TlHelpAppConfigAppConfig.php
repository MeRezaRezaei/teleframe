<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for help.appConfig of help.AppConfig (crc32 dd18782e). */
final class TlHelpAppConfigAppConfig extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_help_app_config_app_config';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'hash' => 'int',
        'config' => 'string',
    ];
}
