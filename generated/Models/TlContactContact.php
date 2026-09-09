<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBool;

/** Constructor model for contact of Contact (crc32 145ade0b). */
final class TlContactContact extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_contact_contact';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'user_id' => 'int',
    ];

    public function mutual(): BelongsTo
    {
        return $this->belongsTo(TlBool::class, 'mutual');
    }
}
