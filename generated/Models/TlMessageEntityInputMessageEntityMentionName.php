<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputUser;

/** Constructor model for inputMessageEntityMentionName of MessageEntity (crc32 208e68c9). */
final class TlMessageEntityInputMessageEntityMentionName extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_entity_input_message_entity_mention_name';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_offset' => 'int',
        'length' => 'int',
    ];

    public function userId(): BelongsTo
    {
        return $this->belongsTo(TlInputUser::class, 'user_id');
    }
}
