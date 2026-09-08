<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param topics (table tl_messages_forum_topics_forum_topics__topics). */
final class TlMessagesForumTopicsForumTopicsTopics extends TlAnchorModel
{
    protected $table = 'tl_messages_forum_topics_forum_topics__topics';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
