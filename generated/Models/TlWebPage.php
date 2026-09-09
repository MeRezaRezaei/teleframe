<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaWebPage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesWebPageWebPage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChannelWebPage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateWebPage;

/** Anchor model for TL type WebPage (spec §4.1). */
final class TlWebPage extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_web_page';

    protected $guarded = [];

    public function webpage(): HasMany
    {
        return $this->hasMany(TlMessageMediaMessageMediaWebPage::class, 'webpage');
    }
    public function webpageMessagesWebPage(): HasMany
    {
        return $this->hasMany(TlMessagesWebPageWebPage::class, 'webpage');
    }
    public function webpageUpdateChannelWebPage(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateChannelWebPage::class, 'webpage');
    }
    public function webpageUpdateWebPage(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateWebPage::class, 'webpage');
    }
}
