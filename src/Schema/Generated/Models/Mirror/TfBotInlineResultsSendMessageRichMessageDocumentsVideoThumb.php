<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfBotInlineResultsSendMessageRichMessageDocumentsVideoThumb extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_bot_inline_results_send_message_rich_message_documents_video_thumbs';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'w' => 'integer',
        'h' => 'integer',
        'size' => 'integer',
        'video_start_ts' => 'float',
        'emoji_id' => 'integer',
        'sticker_id' => 'integer',
    ];
}
