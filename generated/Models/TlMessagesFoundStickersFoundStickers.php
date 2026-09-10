<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesFoundStickersFoundStickersStickers;

/** Constructor model for messages.foundStickers of messages.FoundStickers (crc32 82c9e290). */
final class TlMessagesFoundStickersFoundStickers extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_found_stickers_found_stickers';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'next_offset' => 'int',
        'hash' => 'int',
    ];

    public function stickers(): HasMany
    {
        return $this->tlChild(TlMessagesFoundStickersFoundStickersStickers::class);
    }
}
