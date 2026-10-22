<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Eloquent;

use Illuminate\Database\Eloquent\Model;
use ReflectionProperty;

/**
 * Base class for generated NF5 mirror parent tables.
 * Composite natural key (account_id + TL id); Eloquent treats 'id' as key
 * and AccountScoped guards account isolation. No timestamps (state projection).
 *
 * ## WHY THE TENANT PIN LIVES ON THIS CLASS AND NOT IN THE GENERATED MODELS
 *
 * Every relation built by a model on this base constrains every LEG of its
 * DECLARED foreign key, the tenant leg included. That is done here rather than
 * in the 265 emitted files because `SchemaRegenerator::wipe()` deletes
 * `src/Schema/Generated/Models` wholesale — a security property placed only
 * there is erased by a documented command (D-028). On the base class, the
 * generator's only obligation is to keep emitting `extends TfMirrorModel`, so
 * regeneration cannot remove the guarantee.
 *
 * Note that `AccountScoped` does NOT supply this: `AccountScope::apply()` is a
 * no-op unless an account context is active, and nothing in `src/` ever sets one
 * (D-027), so the global scope does not filter. The relation carries the leg
 * because the relation is the only place it can be carried.
 *
 * ## WHY `$keyType` IS DERIVED HERE AND NOT DECLARED PER MODEL
 *
 * 38 shipped mirror tables have a `varchar(255)` scalar primary key — the
 * opaque, server-issued Telegram handle (`starsTransaction#id`,
 * `botInlineResult#id`, `businessChatLink#link`, `storeBucket#bucket`,
 * `storeObject#name`). `Model::getKeyType()` returning `'int'` there makes
 * Eloquent pick `Relation::whereIntegerInRaw()` on `hasMany`/`hasOne` eager
 * loads, and that helper `(int)`s each value — so an opaque id collapses to
 * `0` and the relation silently matches nothing. The write path is
 * `DB::table()->upsert()` and never consults `$keyType`, which is why this is
 * latent rather than firing today; it is a landmine under the first
 * `with()`/`whereHas()` someone writes.
 *
 * It lives here for the same reason the tenant pin does: `wipe()` never
 * deletes this file, and the generator's only obligation is `extends
 * TfMirrorModel`. Emitting `$keyType` into the generated files would put the
 * property in a tree that a documented command erases (D-028).
 *
 * The generator already emits the answer — it casts every column from the
 * type the ENGINE reports, so a `varchar(255)` key arrives as
 * `$casts['id'] === 'string'`. Reading it back off `$casts` is therefore
 * regeneration-proof and needs no second source of truth.
 *
 * An EXPLICIT `$keyType` on a concrete model still wins, and it must: the
 * hand-authored roots (`TfStarsTransaction`, `TfBotInlineResult`,
 * `TfBusinessChatLink`, …) declare `keyType = 'string'` while deliberately
 * omitting the key from `$casts`, because they hand-maintain that array.
 * Deriving from `$casts` alone would silently flip those 12 models back to
 * `'int'`. This mirrors Laravel's own `HasUniqueStringIds`, which overrides
 * `getKeyType()` the same way — the method is not `final` in 13.31.0.
 *
 * @see PinsDeclaredForeignKeyLegs
 */
abstract class TfMirrorModel extends Model
{
    use PinsDeclaredForeignKeyLegs;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * `string` when this model's own scalar key is a string column, else the
     * declared `$keyType`.
     *
     * An explicit `$keyType` on a concrete subclass is authoritative and is
     * returned unchanged; the derivation only speaks for models that did not
     * speak for themselves — which is every generated one.
     */
    public function getKeyType(): string
    {
        if ((new ReflectionProperty(static::class, 'keyType'))->getDeclaringClass()->getName() !== self::class) {
            return $this->keyType;
        }

        return ($this->casts[$this->getKeyName()] ?? null) === 'string' ? 'string' : 'int';
    }
}
