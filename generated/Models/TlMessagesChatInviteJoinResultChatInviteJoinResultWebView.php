<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesChatInviteJoinResultChatInviDacd8245b982Users;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebViewResult;

/** Constructor model for messages.chatInviteJoinResultWebView of messages.ChatInviteJoinResult (crc32 2f51c337). */
final class TlMessagesChatInviteJoinResultChatInviteJoinResultWebView extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_chat_invite_join_result_chat_invi_dacd8245b982';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'bot_id' => 'int',
    ];

    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesChatInviteJoinResultChatInviDacd8245b982Users::class);
    }

    public function webview(): BelongsTo
    {
        return $this->belongsTo(TlWebViewResult::class, 'webview');
    }
}
