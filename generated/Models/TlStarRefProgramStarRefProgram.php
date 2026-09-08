<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for starRefProgram of StarRefProgram (crc32 dd0c66f2). */
final class TlStarRefProgramStarRefProgram extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_star_ref_program_star_ref_program';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'bot_id' => 'int',
        'commission_permille' => 'int',
        'duration_months' => 'int',
        'end_date' => 'int',
        'daily_revenue_per_user' => 'string',
    ];
}
