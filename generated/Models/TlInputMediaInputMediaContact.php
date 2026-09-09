<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for inputMediaContact of InputMedia (crc32 f8ab7dfb). */
final class TlInputMediaInputMediaContact extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_media_input_media_contact';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'phone_number' => 'string',
        'first_name' => 'string',
        'last_name' => 'string',
        'vcard' => 'string',
    ];
}
