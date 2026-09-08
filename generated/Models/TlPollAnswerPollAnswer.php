<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for pollAnswer of PollAnswer (crc32 4b7d786a). */
final class TlPollAnswerPollAnswer extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_poll_answer_poll_answer';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'text' => 'string',
        'option' => 'string',
        'media' => 'string',
        'added_by' => 'string',
        'date' => 'int',
    ];
}
