<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * NF5 mirror of the SavedReactionTag `title` fact — table
 * tf_saved_reaction_tags_title (2026_09_29_000100:114).
 *
 * WHY A TABLE AND NOT A COLUMN. mirror-catalog.json lists `title` under
 * tf_saved_reaction_tags.children, and a CHILD entry of ANY shape becomes its
 * own table (MirrorTableResolver::resolveTable) — so this is a TEXT fact table,
 * exactly as TfSavedReactionTag's own docblock records. The parent row is
 * always written first and carries no title column at all.
 *
 * 1:1 FACT, NOT A VECTOR. There is no `position` column and no position in the
 * key: a tag carries at most one title, so the table holds at most one row per
 * parent and the key is the parent's own key. That is the difference from the
 * plural reply-markup children, where position IS part of the key.
 *
 * KEY: the DDL primary key is COMPOSITE (account_id, saved_reaction_tag_id)
 * (2026_09_29_000100:119). Eloquent has one key slot, so
 * `saved_reaction_tag_id` — the non-account half, and the SAME surrogate
 * TfSavedReactionTag declares for its own (account_id, saved_reaction_tag_id)
 * key — is the scalar key here. Choosing the parent's surrogate rather than
 * the MirrorChildModel 'account_id' default keeps the child and the parent
 * addressable by the same single value, so the relation below is a plain
 * one-column match on both sides; 'account_id' would have been faithful to
 * nothing and would have made belongsTo() need three columns.
 *
 * Composite-key behaviour is inherently PARTIAL (see TfSavedReactionTag):
 * getKey() and find() are ambiguous across accounts, so address a row by its
 * natural (account_id, saved_reaction_tag_id). No autoincrement, no
 * timestamps — the curated dial is deliberately timestamp-free.
 *
 * @property int $account_id
 * @property int $saved_reaction_tag_id
 * @property string $title
 */
final class TfSavedReactionTagTitle extends TfChildModel
{
    use PinsDeclaredForeignKeyLegs;

    use AccountScoped;

    protected $table = 'tf_saved_reaction_tags_title';

    /**
     * Eloquent key surrogate: the non-account half of the real composite key,
     * identical to the parent's own surrogate.
     */
    protected $primaryKey = 'saved_reaction_tag_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'saved_reaction_tag_id' => 'integer',
    ];

    public function savedReactionTag(): BelongsTo
    {
        return $this->belongsTo(TfSavedReactionTag::class, 'saved_reaction_tag_id', 'saved_reaction_tag_id');
    }
}