<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param entities (table tl_account_resolved_business_chat_links_resol_ee95c75ee22d). */
final class TlAccountResolvedBusinessChatLinksResolC591db58a589Entities extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_account_resolved_business_chat_links_resol_ee95c75ee22d';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
