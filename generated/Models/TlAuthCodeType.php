<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthSentCodeSentCode;

/** Anchor model for TL type auth.CodeType (spec §4.1). */
final class TlAuthCodeType extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_auth_code_type';

    protected $guarded = [];

    public function nextType(): HasMany
    {
        return $this->hasMany(TlAuthSentCodeSentCode::class, 'next_type');
    }
}
