<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputPhoneContact of InputContact (crc32 6a1dc4be). */
final class TlInputContactInputPhoneContact extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_contact_input_phone_contact';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'client_id' => 'int',
        'phone' => 'string',
        'first_name' => 'string',
        'last_name' => 'string',
        'note' => 'string',
    ];
}
