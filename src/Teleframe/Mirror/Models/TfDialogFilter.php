<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * NF5 mirror dialog filter (DialogFilter union: dialogFilter /
 * dialogFilterDefault / dialogFilterChatlist ctors) — table tf_dialog_filters.
 *
 * The real key is composite (account_id, id) with a real telegram-supplied
 * `id` column, so unlike TfDialog (identity IS its peer, no id) no
 * $primaryKey override is needed: TfMirrorModel's 'id' is a real column and
 * AccountScoped guards the account half. All seven sibling facts hang off
 * that id, so the Eloquent hasOne/hasMany relations below are usable here
 * where TfDialog had to fall back to Builder accessors.
 *
 * Only the discriminator and the nine boolean category flags are inline;
 * everything else is a child under (account_id, id): title is the
 * TextWithEntities pair (TfDialogFilterTitle 1:1 + TfDialogFilterTitleEntities
 * 1:N on position), the three peer vectors are 1:N on position, and
 * emoticon / color are 1:1 facts. Row existence in a child = fact
 * existence. flags.?true → BOOLEAN NOT NULL DEFAULT FALSE.
 *
 * §4(e) is UNDECIDED: a filter references *peers*, not dialogs, so this root
 * stands alone and deliberately declares no cross-entity edge. Do NOT add a
 * filters() accessor to TfDialog until that decision lands.
 */
final class TfDialogFilter extends TfMirrorModel
{
    use PinsDeclaredForeignKeyLegs;

    use AccountScoped;

    protected $table = 'tf_dialog_filters';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'contacts' => 'boolean',
        'non_contacts' => 'boolean',
        'groups' => 'boolean',
        'broadcasts' => 'boolean',
        'bots' => 'boolean',
        'exclude_muted' => 'boolean',
        'exclude_read' => 'boolean',
        'exclude_archived' => 'boolean',
        'title_noanimate' => 'boolean',
    ];

    public function title(): HasOne
    {
        return $this->hasOne(TfDialogFilterTitle::class, 'id', 'id');
    }

    public function titleEntities(): HasMany
    {
        return $this->hasMany(TfDialogFilterTitleEntities::class, 'id', 'id');
    }

    public function pinnedPeers(): HasMany
    {
        return $this->hasMany(TfDialogFilterPinnedPeers::class, 'id', 'id');
    }

    public function includePeers(): HasMany
    {
        return $this->hasMany(TfDialogFilterIncludePeers::class, 'id', 'id');
    }

    public function excludePeers(): HasMany
    {
        return $this->hasMany(TfDialogFilterExcludePeers::class, 'id', 'id');
    }

    public function emoticon(): HasOne
    {
        return $this->hasOne(TfDialogFilterEmoticon::class, 'id', 'id');
    }

    public function color(): HasOne
    {
        return $this->hasOne(TfDialogFilterColor::class, 'id', 'id');
    }
}
