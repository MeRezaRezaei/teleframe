<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for requirementToContactPaidMessages of RequirementToContact (crc32 b4f67e93). */
final class TlRequirementToContactRequirementToContactPaidMessages extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_requirement_to_contact_requirement_to_cont_be6f2f636604';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'stars_amount' => 'int',
    ];
}
