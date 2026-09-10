<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsSuggestedStarRefBotsSuggested2b419606faf4Suggested_bots;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsSuggestedStarRefBotsSuggested2b419606faf4Users;

/** Constructor model for payments.suggestedStarRefBots of payments.SuggestedStarRefBots (crc32 b4d5d859). */
final class TlPaymentsSuggestedStarRefBotsSuggestedStarRefBots extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_payments_suggested_star_ref_bots_suggested_2b419606faf4';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'count' => 'int',
        'next_offset' => 'string',
    ];

    public function suggestedBots(): HasMany
    {
        return $this->tlChild(TlPaymentsSuggestedStarRefBotsSuggested2b419606faf4Suggested_bots::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlPaymentsSuggestedStarRefBotsSuggested2b419606faf4Users::class);
    }
}
