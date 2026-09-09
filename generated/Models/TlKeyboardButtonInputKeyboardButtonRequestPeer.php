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

/** Constructor model for inputKeyboardButtonRequestPeer of KeyboardButton (crc32 02b78156). */
final class TlKeyboardButtonInputKeyboardButtonRequestPeer extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_keyboard_button_input_keyboard_button_request_peer';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'name_requested' => 'bool',
        'username_requested' => 'bool',
        'photo_requested' => 'bool',
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
