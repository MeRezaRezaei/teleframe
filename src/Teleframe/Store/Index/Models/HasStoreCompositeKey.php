<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Index\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Composite-primary-key safety net for the three composite-key store truth
 * tables (tf_store_buckets / tf_store_objects / tf_store_media).
 *
 * Eloquent has exactly one primary-key slot ($primaryKey) and no notion of a
 * composite key, so these models point $primaryKey at the LEADING column of
 * the real DDL composite key (account_id) — the only column present in all
 * three keys and the one every store query is already tenant-scoped by.
 *
 * That surrogate is deliberately lossy: getKey()/find()/whereKey() are
 * AMBIGUOUS for these tables (many rows share an account_id), so they are
 * never the right way to address a row. What IS unambiguous is the natural
 * key, so this trait pins the one place Eloquent silently issues a
 * single-column WHERE for an EXISTING model — setKeysForSaveQuery(), which
 * backs both the update half of save() and Model::delete(). Without the
 * override, saving a loaded row would emit `where account_id = ?` and
 * overwrite every row in the tenant.
 *
 * Inserts are unaffected (setKeysForSaveQuery is never consulted for a new
 * model), and whereKey()/find() are left alone on purpose: overriding them
 * would corrupt relationship matching, which needs a single-column key.
 *
 * Provenance: migration 2026_09_20_000100_create_tf_store_tables.php
 * ($table->primary([...]) on all three tables).
 */
trait HasStoreCompositeKey
{
    /**
     * The real DDL composite key columns, in key order.
     *
     * @return list<string>
     */
    abstract protected function storeCompositeKeyColumns(): array;

    /**
     * Narrow an update/delete back to the full natural key instead of the
     * single-column $primaryKey surrogate.
     *
     * Signature mirrors Illuminate\Database\Eloquent\Model (untyped
     * parameter, no return type) so the override stays signature-compatible.
     *
     * @param  Builder  $query
     * @return Builder
     */
    protected function setKeysForSaveQuery($query)
    {
        foreach ($this->storeCompositeKeyColumns() as $column) {
            $query->where($column, $this->getAttribute($column));
        }

        return $query;
    }
}
