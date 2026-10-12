<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * NF5 mirror of the SavedReactionTag ctor (table tf_saved_reaction_tags).
 *
 * A CONTEXT-FREE catalog parent, exactly like TfReaction: the TL type is a
 * reusable reaction tag rather than a chat-owned object, so the catalog keys
 * it on the synthetic `saved_reaction_tag_id` the resolver mints instead of on
 * `tf_messages.id`. Single ctor, so `constructor` is NOT a discriminator here
 * and the table carries no constructor column — the key names the union.
 *
 * The catalog's one child, `title`, is a TEXT fact
 * (mirror-catalog.json: tf_saved_reaction_tags.children) and therefore lands
 * in its own table (tf_saved_reaction_tags_title, 2026_09_29_000100:114)
 * rather than as a column here. The `title()` relation below is a 1:1 hasOne
 * on that table; the child's own mirror of it is TfSavedReactionTagTitle.
 *
 * `count` is the tag's usage count and is type-defaulted to 0 so a partial
 * decomposer write still places.
 *
 * KEY: the DDL primary key is COMPOSITE (account_id, saved_reaction_tag_id)
 * (2026_09_29_000100:111). Eloquent has one key slot, so
 * `saved_reaction_tag_id` — the non-account half — is the key surrogate,
 * following the same override the hand-authored family already uses for a
 * non-'id' natural key (TfAttachMenuBot -> 'bot_id', TfBusinessChatLink ->
 * 'link'). Composite-key behaviour is inherently PARTIAL: getKey() and find()
 * are ambiguous across accounts, so address a row by its natural
 * (account_id, saved_reaction_tag_id). No autoincrement and no timestamps —
 * the curated dial is deliberately timestamp-free.
 *
 * @property int $account_id
 * @property int $saved_reaction_tag_id
 * @property int $count
 */
final class TfSavedReactionTag extends TfMirrorModel
{
    use PinsDeclaredForeignKeyLegs;

    use AccountScoped;

    protected $table = 'tf_saved_reaction_tags';

    /**
     * Eloquent key surrogate: the non-account half of the real composite key.
     */
    protected $primaryKey = 'saved_reaction_tag_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'saved_reaction_tag_id' => 'integer',
        'count' => 'integer',
    ];

    /**
     * The one TEXT fact: a row exists only when the tag carries a title.
     *
     * Matched on the parent's own key surrogate, so the relation is a
     * one-column match on both sides — the account half is not needed because
     * `saved_reaction_tag_id` is the surrogate for (account_id,
     * saved_reaction_tag_id) on BOTH sides of this relation.
     */
    public function title(): HasOne
    {
        return $this->hasOne(TfSavedReactionTagTitle::class, 'saved_reaction_tag_id', 'saved_reaction_tag_id');
    }
}
