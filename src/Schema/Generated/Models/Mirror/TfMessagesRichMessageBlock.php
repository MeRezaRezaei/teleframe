<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfMessagesRichMessageBlock extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_messages_rich_message_blocks';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'published_date' => 'integer',
        'spoiler' => 'boolean',
        'photo_id' => 'integer',
        'webpage_id' => 'integer',
        'autoplay' => 'boolean',
        'loop' => 'boolean',
        'video_id' => 'integer',
        'full_width' => 'boolean',
        'allow_scrolling' => 'boolean',
        'poster_photo_id' => 'integer',
        'w' => 'integer',
        'h' => 'integer',
        'author_photo_id' => 'integer',
        'date' => 'integer',
        'audio_id' => 'integer',
        'bordered' => 'boolean',
        'striped' => 'boolean',
        'reversed' => 'boolean',
        'start' => 'integer',
        'open' => 'boolean',
        'zoom' => 'integer',
    ];
}
