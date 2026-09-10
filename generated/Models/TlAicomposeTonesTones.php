<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAicomposeTonesTonesTones;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAicomposeTonesTonesUsers;

/** Constructor model for aicompose.tones of aicompose.Tones (crc32 6c9d0efe). */
final class TlAicomposeTonesTones extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_aicompose_tones_tones';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'hash' => 'int',
    ];

    public function tones(): HasMany
    {
        return $this->tlChild(TlAicomposeTonesTonesTones::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlAicomposeTonesTonesUsers::class);
    }
}
