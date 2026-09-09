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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialogFilterDialogFilterChatlistInclude_peers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialogFilterDialogFilterChatlistPinned_peers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTextWithEntities;

/** Constructor model for dialogFilterChatlist of DialogFilter (crc32 96537bd7). */
final class TlDialogFilterDialogFilterChatlist extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_dialog_filter_dialog_filter_chatlist';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'has_my_invites' => 'bool',
        'title_noanimate' => 'bool',
        'tl_id' => 'int',
        'emoticon' => 'string',
        'color' => 'int',
    ];

    public function pinnedPeers(): HasMany
    {
        return $this->tlChild(TlDialogFilterDialogFilterChatlistPinned_peers::class);
    }
    public function includePeers(): HasMany
    {
        return $this->tlChild(TlDialogFilterDialogFilterChatlistInclude_peers::class);
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(TlTextWithEntities::class, 'title');
    }
}
