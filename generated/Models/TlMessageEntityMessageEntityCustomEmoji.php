<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for messageEntityCustomEmoji of MessageEntity (crc32 c8cf05f8). */
final class TlMessageEntityMessageEntityCustomEmoji extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_entity_message_entity_custom_emoji';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_offset' => 'int',
        'length' => 'int',
        'document_id' => 'int',
    ];
}
