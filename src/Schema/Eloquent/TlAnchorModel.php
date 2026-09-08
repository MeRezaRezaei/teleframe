<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

/**
 * Base for anchor models (spec §4.1): UUIDv7 PK assigned on create.
 */
abstract class TlAnchorModel extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected string $uuidColumn = 'id';

    protected static function booted(): void
    {
        static::creating(static function (self $model): void {
            if ($model->getAttribute($model->uuidColumn) === null) {
                $model->setAttribute($model->uuidColumn, (string) new UuidV7());
            }
        });
    }

    /** Discriminator: TL constructor crc32 of this instance. */
    public function getConstructorIdAttribute(): int
    {
        return (int) $this->attributes['constructor_id'];
    }
}
