<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for highScore of HighScore (crc32 73a379eb). */
final class TlHighScoreHighScore extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_high_score_high_score';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'pos' => 'int',
        'user_id' => 'int',
        'score' => 'int',
    ];
}
