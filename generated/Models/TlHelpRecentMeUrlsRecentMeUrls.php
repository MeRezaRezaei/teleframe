<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpRecentMeUrlsRecentMeUrlsChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpRecentMeUrlsRecentMeUrlsUrls;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpRecentMeUrlsRecentMeUrlsUsers;

/** Constructor model for help.recentMeUrls of help.RecentMeUrls (crc32 0e0310d7). */
final class TlHelpRecentMeUrlsRecentMeUrls extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_help_recent_me_urls_recent_me_urls';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function urls(): HasMany
    {
        return $this->tlChild(TlHelpRecentMeUrlsRecentMeUrlsUrls::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlHelpRecentMeUrlsRecentMeUrlsChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlHelpRecentMeUrlsRecentMeUrlsUsers::class);
    }
}
