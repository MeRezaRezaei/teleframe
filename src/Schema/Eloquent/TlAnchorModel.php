<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * Base model for the curated mirror tables (tf_users, tf_messages, ...).
 *
 * The shipped domain tables FLATTEN the TL union: one row per
 * (account_id, <key>), a `constructor` text column carrying the concrete TL
 * ctor name as the discriminator, one column per ctor field (type-defaulted
 * so a partial write still places), and the vector/FK facts as child tables
 * keyed off the parent. There is NO `tl_data` JSONB and NO numeric
 * `constructor_id` anywhere in the shipped dial — both belong to the abandoned
 * TDLib-style shape and are asserted absent by tests/Pg/FullMirrorPgTest.php
 * and tests/Ingest/UpdateIngestorTest.php.
 *
 * What is left here is only the Eloquent base the generated Tl* models
 * inherit: no autoincrement anywhere on the domain surface, and an integer
 * primary-key type. The discriminator is the plain `constructor` attribute.
 *
 * ## WHY THIS CLASS CARRIES THE TENANT PIN TOO
 *
 * The 11 `Tl*` anchors are the PARENT side of the mirror's relations — 211 of
 * the generated layer's 422 relation methods are `hasMany` off an anchor — so
 * pinning only `TfMirrorModel` would leave the parent side of the leak open
 * while closing the child side. The anchors share no base with `TfMirrorModel`,
 * so the trait is applied to each. See {@see PinsDeclaredForeignKeyLegs} for why
 * the property lives on hand-written bases rather than in the regenerable tree
 * (D-028).
 *
 * @see PinsDeclaredForeignKeyLegs
 */
abstract class TlAnchorModel extends Model
{
    use PinsDeclaredForeignKeyLegs;

    /**
     * No DB autoincrement anywhere on the domain surface: global-ID tables
     * carry the Telegram native ID as (part of) the PK, surrogate-ID tables
     * (messages, updates, ...) take sqlite-rowid / app-assigned ids. Leaving
     * this true makes Eloquent overwrite an explicitly filled native id with
     * the connection's lastInsertId() after insert (sqlite composite PKs
     * report 1), so the returned model disagrees with its own DB row.
     *
     * @var bool
     */
    public $incrementing = false;

    /** @var string PK type for Eloquent */
    protected $keyType = 'int';
}
