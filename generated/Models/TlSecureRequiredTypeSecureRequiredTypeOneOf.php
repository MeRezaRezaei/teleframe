<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureRequiredTypeSecureRequiredTypeOneOfTypes;

/** Constructor model for secureRequiredTypeOneOf of SecureRequiredType (crc32 027477b4). */
final class TlSecureRequiredTypeSecureRequiredTypeOneOf extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_secure_required_type_secure_required_type_one_of';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function types(): HasMany
    {
        return $this->tlChild(TlSecureRequiredTypeSecureRequiredTypeOneOfTypes::class);
    }
}
