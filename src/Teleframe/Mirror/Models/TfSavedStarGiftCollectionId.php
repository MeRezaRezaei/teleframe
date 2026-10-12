<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:N collection_id child — the optional Vector<int> collections, one row per
 * vector slot.
 *
 * The element's value column is `value`, not `collection_id`, because that is
 * the name `MirrorTableResolver::scalarValueColumn()` emits for every scalar
 * vector element type (src/Schema/Mirror/MirrorTableResolver.php:534-545) and
 * the name the shipped DDL declares
 * (src/Laravel/Migrations/2026_09_14_200040_create_tf_stars_business_tables.php).
 * `collection_id` is the WIRE FIELD, not the column — casting it here made
 * every Eloquent write of this child raise "table
 * tf_saved_star_gifts_collection_id has no column named collection_id"
 * (measured 2026-10-04, tests/Mirror/StarsBusinessMirrorTest.php:258).
 */
final class TfSavedStarGiftCollectionId extends TfChildModel
{
    use PinsDeclaredForeignKeyLegs;

    use AccountScoped;

    protected $table = 'tf_saved_star_gifts_collection_id';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'position' => 'int',
        'value' => 'int',
    ];

    public function savedGift(): BelongsTo
    {
        return $this->belongsTo(TfSavedStarGift::class, 'id', 'id');
    }
}
