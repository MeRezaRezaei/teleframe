<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTodoList;

/** Constructor model for inputMediaTodo of InputMedia (crc32 9fc55fde). */
final class TlInputMediaInputMediaTodo extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_media_input_media_todo';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function todo(): BelongsTo
    {
        return $this->belongsTo(TlTodoList::class, 'todo');
    }
}
