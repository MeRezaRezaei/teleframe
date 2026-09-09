<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for payments.starGiftCollectionsNotModified of payments.StarGiftCollections (crc32 a0ba4f17). */
final class TlPaymentsStarGiftCollectionsStarGiftCollectionsNotModified extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_payments_star_gift_collections_star_gift_c_72774827a67d';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
