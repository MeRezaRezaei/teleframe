<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for savedPhoneContact of SavedContact (crc32 1142bd56). */
final class TlSavedContactSavedPhoneContact extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_saved_contact_saved_phone_contact';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'phone' => 'string',
        'first_name' => 'string',
        'last_name' => 'string',
        'date' => 'int',
    ];
}
