<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for messageEntityPhone of MessageEntity (crc32 9b69e34b). */
final class TlMessageEntityMessageEntityPhone extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_entity_message_entity_phone';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_offset' => 'int',
        'length' => 'int',
    ];
}
