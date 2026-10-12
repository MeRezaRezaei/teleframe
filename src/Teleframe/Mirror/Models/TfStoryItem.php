<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * NF5 mirror of the StoryItem union (table tf_story_items).
 *
 * The bare StoryItem row: `id` is the Telegram story item id and IS the
 * Telegram-owned id, so no synthetic key is minted here. Three ctors share
 * the row and are discriminated by `constructor`: storyItemDeleted /
 * storyItemSkipped / storyItem.
 *
 * Reached from the update route by MirrorNestedSourceWalker on the declared
 * source edge updateStory.story (mirror-catalog.json), i.e. the story rows
 * arrive with a story update rather than through a parent FK — which is why
 * this is a root with no parent relation.
 *
 * Note this is NOT tl_stories (the story-peer row, bound by the generated
 * TlStory); it is the per-item table.
 *
 * KEY: the DDL primary key is COMPOSITE (account_id, id)
 * (2026_09_29_000100:73), so `id` — TfMirrorModel's default — is the key
 * surrogate and no override is needed. It is a SIGNED 32-bit integer (`:62`),
 * so the cast is deliberately not widened. Composite-key behaviour is
 * inherently PARTIAL: find() is ambiguous across accounts, so address a row by
 * its natural (account_id, id). No autoincrement and no timestamps — the
 * curated dial is deliberately timestamp-free.
 *
 * @property int $account_id
 * @property int $id
 * @property string $constructor
 */
final class TfStoryItem extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_story_items';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
    ];
}
