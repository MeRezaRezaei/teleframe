<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialogFilterDialogFilterExclude_peers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialogFilterDialogFilterInclude_peers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialogFilterDialogFilterPinned_peers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTextWithEntities;

/** Constructor model for dialogFilter of DialogFilter (crc32 aa472651). */
final class TlDialogFilterDialogFilter extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_dialog_filter_dialog_filter';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'contacts' => 'bool',
        'non_contacts' => 'bool',
        'groups' => 'bool',
        'broadcasts' => 'bool',
        'bots' => 'bool',
        'exclude_muted' => 'bool',
        'exclude_read' => 'bool',
        'exclude_archived' => 'bool',
        'title_noanimate' => 'bool',
        'tl_id' => 'int',
        'emoticon' => 'string',
        'color' => 'int',
    ];

    public function pinnedPeers(): HasMany
    {
        return $this->tlChild(TlDialogFilterDialogFilterPinned_peers::class);
    }
    public function includePeers(): HasMany
    {
        return $this->tlChild(TlDialogFilterDialogFilterInclude_peers::class);
    }
    public function excludePeers(): HasMany
    {
        return $this->tlChild(TlDialogFilterDialogFilterExclude_peers::class);
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(TlTextWithEntities::class, 'title');
    }
}
