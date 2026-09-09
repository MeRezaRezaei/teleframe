<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpPremiumPromoPremiumPromoPeriod_options;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpPremiumPromoPremiumPromoStatus_entities;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpPremiumPromoPremiumPromoUsers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpPremiumPromoPremiumPromoVideo_sections;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpPremiumPromoPremiumPromoVideos;

/** Constructor model for help.premiumPromo of help.PremiumPromo (crc32 5334759c). */
final class TlHelpPremiumPromoPremiumPromo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_help_premium_promo_premium_promo';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'status_text' => 'string',
    ];

    public function statusEntities(): HasMany
    {
        return $this->tlChild(TlHelpPremiumPromoPremiumPromoStatus_entities::class);
    }
    public function videoSections(): HasMany
    {
        return $this->tlChild(TlHelpPremiumPromoPremiumPromoVideo_sections::class);
    }
    public function videos(): HasMany
    {
        return $this->tlChild(TlHelpPremiumPromoPremiumPromoVideos::class);
    }
    public function periodOptions(): HasMany
    {
        return $this->tlChild(TlHelpPremiumPromoPremiumPromoPeriod_options::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlHelpPremiumPromoPremiumPromoUsers::class);
    }
}
