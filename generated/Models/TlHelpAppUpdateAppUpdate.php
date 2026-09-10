<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpAppUpdateAppUpdateEntities;

/** Constructor model for help.appUpdate of help.AppUpdate (crc32 ccbbce30). */
final class TlHelpAppUpdateAppUpdate extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_help_app_update_app_update';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'can_not_skip' => 'bool',
        'tl_id' => 'int',
        'version' => 'string',
        'text' => 'string',
        'url' => 'string',
    ];

    public function entities(): HasMany
    {
        return $this->tlChild(TlHelpAppUpdateAppUpdateEntities::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(TlDocument::class, 'document');
    }
    public function sticker(): BelongsTo
    {
        return $this->belongsTo(TlDocument::class, 'sticker');
    }
}
