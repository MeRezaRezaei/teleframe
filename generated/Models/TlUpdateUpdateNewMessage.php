<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessage;

/** Constructor model for updateNewMessage of Update (crc32 1f2b0afd). */
final class TlUpdateUpdateNewMessage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_new_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'pts' => 'int',
        'pts_count' => 'int',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(TlMessage::class, 'message');
    }
}
