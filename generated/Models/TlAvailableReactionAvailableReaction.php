<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocument;

/** Constructor model for availableReaction of AvailableReaction (crc32 c077ec01). */
final class TlAvailableReactionAvailableReaction extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_available_reaction_available_reaction';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'inactive' => 'bool',
        'premium' => 'bool',
        'reaction' => 'string',
        'title' => 'string',
    ];

    public function staticIcon(): BelongsTo
    {
        return $this->belongsTo(TlDocument::class, 'static_icon');
    }
    public function appearAnimation(): BelongsTo
    {
        return $this->belongsTo(TlDocument::class, 'appear_animation');
    }
    public function selectAnimation(): BelongsTo
    {
        return $this->belongsTo(TlDocument::class, 'select_animation');
    }
    public function activateAnimation(): BelongsTo
    {
        return $this->belongsTo(TlDocument::class, 'activate_animation');
    }
    public function effectAnimation(): BelongsTo
    {
        return $this->belongsTo(TlDocument::class, 'effect_animation');
    }
    public function aroundAnimation(): BelongsTo
    {
        return $this->belongsTo(TlDocument::class, 'around_animation');
    }
    public function centerIcon(): BelongsTo
    {
        return $this->belongsTo(TlDocument::class, 'center_icon');
    }
}
