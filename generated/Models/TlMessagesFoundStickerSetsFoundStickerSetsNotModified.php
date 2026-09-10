<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for messages.foundStickerSetsNotModified of messages.FoundStickerSets (crc32 0d54b65d). */
final class TlMessagesFoundStickerSetsFoundStickerSetsNotModified extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_found_sticker_sets_found_sticker__68e11d7b41b6';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
