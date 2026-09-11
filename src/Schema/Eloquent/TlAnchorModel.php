<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * Base model for TL domain tables (TDLib-style schema).
 *
 * Each domain table (tf_users, tf_messages, etc.) stores extracted query
 * columns + tl_data JSONB. This base class sets up integer PK, the
 * constructor_id accessor, and the tl_data array cast.
 */
abstract class TlAnchorModel extends Model
{
    /** @var bool Eloquent auto-increment (Telegram IDs are integers) */
    public $incrementing = true;

    /** @var string PK type for Eloquent */
    protected $keyType = 'int';

    /** @var array<string, string> */
    protected $casts = [
        'tl_data' => 'array',
    ];

    /** Discriminator: TL constructor crc32 of this instance. */
    public function getConstructorIdAttribute(): int
    {
        return (int) $this->attributes['constructor_id'];
    }
}
