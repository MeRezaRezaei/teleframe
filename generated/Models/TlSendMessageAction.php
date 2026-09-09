<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageActionDecryptedMessageActionTyping;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChannelUserTyping;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChatUserTyping;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateUserTyping;

/** Anchor model for TL type SendMessageAction (spec §4.1). */
final class TlSendMessageAction extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_send_message_action';

    protected $guarded = [];

    public function action(): HasMany
    {
        return $this->hasMany(TlDecryptedMessageActionDecryptedMessageActionTyping::class, 'action');
    }
    public function actionUpdateChannelUserTyping(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateChannelUserTyping::class, 'action');
    }
    public function actionUpdateChatUserTyping(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateChatUserTyping::class, 'action');
    }
    public function actionUpdateUserTyping(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateUserTyping::class, 'action');
    }
}
