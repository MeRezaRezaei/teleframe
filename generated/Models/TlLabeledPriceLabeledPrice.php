<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for labeledPrice of LabeledPrice (crc32 cb296bf8). */
final class TlLabeledPriceLabeledPrice extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_labeled_price_labeled_price';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'label' => 'string',
        'amount' => 'int',
    ];
}
