<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for restrictionReason of RestrictionReason (crc32 d072acb4). */
final class TlRestrictionReasonRestrictionReason extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_restriction_reason_restriction_reason';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'platform' => 'string',
        'reason' => 'string',
        'text' => 'string',
    ];
}
