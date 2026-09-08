<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for destroy_session_ok of DestroySessionRes (crc32 e22045fc). */
final class TlDestroySessionResDestroySessionOk extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_destroy_session_res_destroy_session_ok';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'session_id' => 'int',
    ];
}
