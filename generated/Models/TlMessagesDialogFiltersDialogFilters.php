<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesDialogFiltersDialogFiltersFilters;

/** Constructor model for messages.dialogFilters of messages.DialogFilters (crc32 2ad93719). */
final class TlMessagesDialogFiltersDialogFilters extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_dialog_filters_dialog_filters';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'tags_enabled' => 'bool',
    ];

    public function filters(): HasMany
    {
        return $this->tlChild(TlMessagesDialogFiltersDialogFiltersFilters::class);
    }
}
