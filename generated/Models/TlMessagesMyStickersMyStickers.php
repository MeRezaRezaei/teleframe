<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesMyStickersMyStickersSets;

/** Constructor model for messages.myStickers of messages.MyStickers (crc32 faff629d). */
final class TlMessagesMyStickersMyStickers extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_my_stickers_my_stickers';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'count' => 'int',
    ];

    public function sets(): HasMany
    {
        return $this->tlChild(TlMessagesMyStickersMyStickersSets::class);
    }
}
