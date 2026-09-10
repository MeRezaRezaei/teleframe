<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputFile;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlVideoSize;

/** Constructor model for inputChatUploadedPhoto of InputChatPhoto (crc32 bdcdaec0). */
final class TlInputChatPhotoInputChatUploadedPhoto extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_chat_photo_input_chat_uploaded_photo';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'video_start_ts' => 'float',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(TlInputFile::class, 'file');
    }
    public function video(): BelongsTo
    {
        return $this->belongsTo(TlInputFile::class, 'video');
    }
    public function videoEmojiMarkup(): BelongsTo
    {
        return $this->belongsTo(TlVideoSize::class, 'video_emoji_markup');
    }
}
