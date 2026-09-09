<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonStyle;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRequestPeerType;

/** Constructor model for keyboardButtonRequestPeer of KeyboardButton (crc32 5b0f15f5). */
final class TlKeyboardButtonKeyboardButtonRequestPeer extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_keyboard_button_keyboard_button_request_peer';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'text' => 'string',
        'button_id' => 'int',
        'max_quantity' => 'int',
    ];

    public function style(): BelongsTo
    {
        return $this->belongsTo(TlKeyboardButtonStyle::class, 'style');
    }
    public function peerType(): BelongsTo
    {
        return $this->belongsTo(TlRequestPeerType::class, 'peer_type');
    }
}
