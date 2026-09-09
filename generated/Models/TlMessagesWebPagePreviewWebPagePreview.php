<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMedia;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesWebPagePreviewWebPagePreviewChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesWebPagePreviewWebPagePreviewUsers;

/** Constructor model for messages.webPagePreview of messages.WebPagePreview (crc32 8c9a88ac). */
final class TlMessagesWebPagePreviewWebPagePreview extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_web_page_preview_web_page_preview';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function chats(): HasMany
    {
        return $this->tlChild(TlMessagesWebPagePreviewWebPagePreviewChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesWebPagePreviewWebPagePreviewUsers::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(TlMessageMedia::class, 'media');
    }
}
