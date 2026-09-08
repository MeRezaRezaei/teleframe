<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaToDoCompletions;

/** Constructor model for messageMediaToDo of MessageMedia (crc32 8a53b014). */
final class TlMessageMediaMessageMediaToDo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_media_message_media_to_do';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'todo' => 'string',
    ];

    public function completions(): HasMany
    {
        return $this->tlChild(TlMessageMediaMessageMediaToDoCompletions::class);
    }
}
