<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for messages.quickRepliesNotModified of messages.QuickReplies (crc32 5f91eb5b). */
final class TlMessagesQuickRepliesQuickRepliesNotModified extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_quick_replies_quick_replies_not_modified';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
