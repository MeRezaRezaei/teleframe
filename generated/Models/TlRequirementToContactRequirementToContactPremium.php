<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for requirementToContactPremium of RequirementToContact (crc32 e581e4e9). */
final class TlRequirementToContactRequirementToContactPremium extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_requirement_to_contact_requirement_to_contact_premium';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
