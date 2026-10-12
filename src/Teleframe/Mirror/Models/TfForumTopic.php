<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * NF5 mirror of the ForumTopic union (table tf_forum_topics).
 *
 * The bare ForumTopic row: `id` is the Telegram forum topic id and IS the
 * Telegram-owned id, so this is the one catalog parent that keeps a native
 * `id` rather than a synthetic one. Two ctors share the row and are
 * discriminated by `constructor`: forumTopicDeleted / forumTopic.
 *
 * The catalog carries no child facts for this type (children: [] in
 * mirror-catalog.json), so the whole union fits the base row and there is
 * nothing to attach.
 *
 * KEY: the DDL primary key is COMPOSITE (account_id, id)
 * (2026_09_29_000100:65), so `id` — which happens to be TfMirrorModel's
 * default — is the key surrogate and no override is needed. It is a SIGNED
 * 32-bit integer (`:62`), not a 64-bit one, so the cast is deliberately not
 * widened. Composite-key behaviour is inherently PARTIAL: find() is ambiguous
 * across accounts, so address a row by its natural (account_id, id).
 * No autoincrement and no timestamps — the curated dial is deliberately
 * timestamp-free.
 *
 * @property int $account_id
 * @property int $id
 * @property string $constructor
 */
final class TfForumTopic extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_forum_topics';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
    ];
}
