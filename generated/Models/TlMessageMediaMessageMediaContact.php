<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for messageMediaContact of MessageMedia (crc32 70322949). */
final class TlMessageMediaMessageMediaContact extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_media_message_media_contact';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'phone_number' => 'string',
        'first_name' => 'string',
        'last_name' => 'string',
        'vcard' => 'string',
        'user_id' => 'int',
    ];
}
