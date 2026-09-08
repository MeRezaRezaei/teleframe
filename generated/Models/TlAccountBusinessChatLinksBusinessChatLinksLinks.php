<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param links (table tl_account_business_chat_links_business_chat_links__links). */
final class TlAccountBusinessChatLinksBusinessChatLinksLinks extends TlAnchorModel
{
    protected $table = 'tl_account_business_chat_links_business_chat_links__links';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
