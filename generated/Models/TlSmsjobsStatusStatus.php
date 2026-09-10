<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for smsjobs.status of smsjobs.Status (crc32 2aee9191). */
final class TlSmsjobsStatusStatus extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_smsjobs_status_status';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'allow_international' => 'bool',
        'recent_sent' => 'int',
        'recent_since' => 'int',
        'recent_remains' => 'int',
        'total_sent' => 'int',
        'total_since' => 'int',
        'last_gift_slug' => 'string',
        'terms_url' => 'string',
    ];
}
