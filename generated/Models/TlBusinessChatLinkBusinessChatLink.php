<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessChatLinkBusinessChatLinkEntities;

/** Constructor model for businessChatLink of BusinessChatLink (crc32 b4ae666f). */
final class TlBusinessChatLinkBusinessChatLink extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_business_chat_link_business_chat_link';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'link' => 'string',
        'message' => 'string',
        'title' => 'string',
        'views' => 'int',
    ];

    public function entities(): HasMany
    {
        return $this->tlChild(TlBusinessChatLinkBusinessChatLinkEntities::class);
    }
}
