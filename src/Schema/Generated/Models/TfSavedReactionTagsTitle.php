<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_saved_reaction_tags_title`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 *
 * Relations declared on this table: 1.
 */
final class TfSavedReactionTagsTitle extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_saved_reaction_tags_title';

    protected $guarded = [];

    /** No autoincrement anywhere on the mirror: keys are Telegram-native. */
    public $incrementing = false;

    /**
     * Eloquent gets ONE scalar key. The real key is the composite (account_id, saved_reaction_tag_id),
     * which the database enforces; `saved_reaction_tag_id` is total only WITHIN an account, and
     * AccountScope guarantees that. So `find()` is account-scoped by construction:
     * reach for a relation or an explicit where, never a bare find().
     */
    protected $primaryKey = 'saved_reaction_tag_id';

    public $timestamps = false;

    /**
     * Derived from the type the ENGINE reports for each column — never from a
     * docblock, and never from SQLite, whose affinity collapses integer(),
     * bigInteger() and tinyInteger() alike and drops width and signedness.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'account_id' => 'int',
        'saved_reaction_tag_id' => 'int',
        'title' => 'string',
    ];

    /** Declared foreign key: `tf_saved_reaction_tags_title`(account_id, saved_reaction_tag_id) -> `tf_saved_reaction_tags`(account_id, saved_reaction_tag_id). Keyed on `saved_reaction_tag_id` = `saved_reaction_tag_id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function savedReactionTags(): BelongsTo
    {
        return $this->belongsTo(TfSavedReactionTags::class, 'saved_reaction_tag_id', 'saved_reaction_tag_id');
    }
}
