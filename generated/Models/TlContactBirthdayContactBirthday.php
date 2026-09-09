<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBirthday;

/** Constructor model for contactBirthday of ContactBirthday (crc32 1d998733). */
final class TlContactBirthdayContactBirthday extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_contact_birthday_contact_birthday';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'contact_id' => 'int',
    ];

    public function birthday(): BelongsTo
    {
        return $this->belongsTo(TlBirthday::class, 'birthday');
    }
}
