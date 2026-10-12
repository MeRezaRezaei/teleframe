<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * NF5 mirror of the Reaction union (table tf_reactions).
 *
 * Reaction is a CONTEXT-FREE catalog parent: its own TL type carries no
 * Telegram-owned id (it is a reaction ELEMENT, not a chat message), so the
 * catalog keys it on the synthetic `reaction_id` the resolver mints, not on
 * `tf_messages.id`. That is why this is a root with no parent FK and why its
 * id space is disjoint from every other Reaction row's consumers.
 *
 * Four ctors share the row and are discriminated by `constructor`:
 * reactionEmpty / reactionEmoji / reactionCustomEmoji / reactionPaid.
 *
 * KEY: the DDL primary key is COMPOSITE (account_id, reaction_id)
 * (2026_09_29_000100:57). Eloquent has one key slot, so `reaction_id` — the
 * non-account half — is the key surrogate; this follows the same override the
 * hand-authored family already uses for a non-'id' natural key
 * (TfAttachMenuBot -> 'bot_id', TfBusinessChatLink -> 'link').
 * Composite-key behaviour is inherently PARTIAL: getKey() and find() are
 * ambiguous across accounts, so address a row by its natural
 * (account_id, reaction_id). No autoincrement and no timestamps — the curated
 * dial is deliberately timestamp-free, and there is no Telegram-native id here
 * to carry a surrogate.
 *
 * @property int $account_id
 * @property int $reaction_id
 * @property string $constructor
 * @property string $emoticon
 */
final class TfReaction extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_reactions';

    /**
     * Eloquent key surrogate: the non-account half of the real composite key.
     */
    protected $primaryKey = 'reaction_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'reaction_id' => 'integer',
    ];
}
