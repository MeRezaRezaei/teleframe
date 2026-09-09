<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for payments.starGiftActiveAuctionsNotModified of payments.StarGiftActiveAuctions (crc32 db33dad0). */
final class TlPaymentsStarGiftActiveAuctionsStarGiftActiveAuctionsNotModified extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_payments_star_gift_active_auctions_star_gi_1c32f1e9e4ab';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
