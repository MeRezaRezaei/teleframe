<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param correct_answers (table tl_input_media_input_media_poll__correct_answers). */
final class TlInputMediaInputMediaPollCorrect_answers extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_media_input_media_poll__correct_answers';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
