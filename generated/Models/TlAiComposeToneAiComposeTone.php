<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAiComposeToneExample;

/** Constructor model for aiComposeTone of AiComposeTone (crc32 cff63ea9). */
final class TlAiComposeToneAiComposeTone extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_ai_compose_tone_ai_compose_tone';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'creator' => 'bool',
        'tl_id' => 'int',
        'access_hash' => 'int',
        'slug' => 'string',
        'title' => 'string',
        'emoji_id' => 'int',
        'prompt' => 'string',
        'installs_count' => 'int',
        'author_id' => 'int',
    ];

    public function exampleEnglish(): BelongsTo
    {
        return $this->belongsTo(TlAiComposeToneExample::class, 'example_english');
    }
}
