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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesWebPageWebPageChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesWebPageWebPageUsers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebPage;

/** Constructor model for messages.webPage of messages.WebPage (crc32 fd5e12bd). */
final class TlMessagesWebPageWebPage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_web_page_web_page';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function chats(): HasMany
    {
        return $this->tlChild(TlMessagesWebPageWebPageChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesWebPageWebPageUsers::class);
    }

    public function webpage(): BelongsTo
    {
        return $this->belongsTo(TlWebPage::class, 'webpage');
    }
}
