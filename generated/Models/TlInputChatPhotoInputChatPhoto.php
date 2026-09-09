<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPhoto;

/** Constructor model for inputChatPhoto of InputChatPhoto (crc32 8953ad37). */
final class TlInputChatPhotoInputChatPhoto extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_chat_photo_input_chat_photo';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function id(): BelongsTo
    {
        return $this->belongsTo(TlInputPhoto::class, 'tl_id');
    }
}
