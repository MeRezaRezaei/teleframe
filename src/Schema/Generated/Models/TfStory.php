<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

/**
 * Telegram story — tf_stories.
 *
 * PK: (story_id, peer_id, peer_type, account_id).
 * Story IDs are per-peer, not globally unique.
 */
class TfStory extends TfModel
{
    protected $table = 'tf_stories';

    /** Composite PK — Eloquent uses story_id as logical key. */
    protected $primaryKey = 'story_id';

    /** @var list<string> */
    protected $fillable = [
        'story_id', 'peer_id', 'peer_type', 'account_id',
        'date', 'expire_date', 'caption',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'tl_data' => 'array',
        'is_out' => 'boolean',
        'is_pinned' => 'boolean',
        'is_public' => 'boolean',
        'is_close_friends' => 'boolean',
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
    ];
}
