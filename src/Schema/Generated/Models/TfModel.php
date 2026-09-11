<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScope;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/**
 * Base model for tf_* domain tables.
 *
 * All tf_ tables use composite PKs (entity_id, account_id) and store
 * the full TL payload in tl_data JSONB. This base sets up:
 * - Non-incrementing integer PK (Telegram IDs)
 * - tl_data array cast
 * - AccountScope global scope
 */
abstract class TfModel extends TlAnchorModel
{
    public $incrementing = false;

    protected $keyType = 'int';

    /** @var array<string, string> */
    protected $casts = [
        'tl_data' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new AccountScope());
    }
}
