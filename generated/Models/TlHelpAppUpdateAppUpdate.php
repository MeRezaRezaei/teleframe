<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpAppUpdateAppUpdateEntities;

/** Constructor model for help.appUpdate of help.AppUpdate (crc32 ccbbce30). */
final class TlHelpAppUpdateAppUpdate extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_help_app_update_app_update';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'can_not_skip' => 'bool',
        'tl_id' => 'int',
        'version' => 'string',
        'text' => 'string',
        'document' => 'string',
        'url' => 'string',
        'sticker' => 'string',
    ];

    public function entities(): HasMany
    {
        return $this->tlChild(TlHelpAppUpdateAppUpdateEntities::class);
    }
}
