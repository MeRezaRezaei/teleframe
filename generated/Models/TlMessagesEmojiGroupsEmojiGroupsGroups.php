<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param groups (table tl_messages_emoji_groups_emoji_groups__groups). */
final class TlMessagesEmojiGroupsEmojiGroupsGroups extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_emoji_groups_emoji_groups__groups';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
