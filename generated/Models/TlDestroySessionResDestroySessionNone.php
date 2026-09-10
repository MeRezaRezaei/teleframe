<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for destroy_session_none of DestroySessionRes (crc32 62d350c9). */
final class TlDestroySessionResDestroySessionNone extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_destroy_session_res_destroy_session_none';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'session_id' => 'int',
    ];
}
