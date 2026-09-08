<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messageEntityDiffDelete of MessageEntity (crc32 0652c1c5). */
final class TlMessageEntityMessageEntityDiffDelete extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_entity_message_entity_diff_delete';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_offset' => 'int',
        'length' => 'int',
    ];
}
