<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for new_session_created of NewSession (crc32 9ec20908). */
final class TlNewSessionNewSessionCreated extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_new_session_new_session_created';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'first_msg_id' => 'int',
        'unique_id' => 'int',
        'server_salt' => 'int',
    ];
}
