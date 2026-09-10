<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * Base model for TL entity tables (spec 4): Telegram-native integer PK,
 * no UUID generation. Global-ID types use Telegram's own ID as PK;
 * scoped/identity-less types use auto-increment.
 */
abstract class TlAnchorModel extends Model
{
    /** @var bool Eloquent auto-increment (Telegram IDs are integers) */
    public $incrementing = true;

    /** @var string<int, int> PK type for Eloquent */
    protected $keyType = 'int';

    /** Discriminator: TL constructor crc32 of this instance. */
    public function getConstructorIdAttribute(): int
    {
        return (int) $this->attributes['constructor_id'];
    }
}
