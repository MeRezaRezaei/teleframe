<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for http_wait of HttpWait (crc32 9299359f). */
final class TlHttpWaitHttpWait extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_http_wait_http_wait';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'max_delay' => 'int',
        'wait_after' => 'int',
        'max_wait' => 'int',
    ];
}
