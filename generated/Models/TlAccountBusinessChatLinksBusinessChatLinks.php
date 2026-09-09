<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountBusinessChatLinksBusinessChatLinksChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountBusinessChatLinksBusinessChatLinksLinks;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountBusinessChatLinksBusinessChatLinksUsers;

/** Constructor model for account.businessChatLinks of account.BusinessChatLinks (crc32 ec43a2d1). */
final class TlAccountBusinessChatLinksBusinessChatLinks extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_account_business_chat_links_business_chat_links';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function links(): HasMany
    {
        return $this->tlChild(TlAccountBusinessChatLinksBusinessChatLinksLinks::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlAccountBusinessChatLinksBusinessChatLinksChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlAccountBusinessChatLinksBusinessChatLinksUsers::class);
    }
}
