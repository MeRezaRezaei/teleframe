<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialogDialog;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlForumTopicForumTopic;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSavedDialogMonoForumDialog;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateDraftMessage;

/** Anchor model for TL type DraftMessage (spec §4.1). */
final class TlDraftMessage extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_draft_message_draft_message';

    protected $guarded = [];

    public function draft(): HasMany
    {
        return $this->hasMany(TlDialogDialog::class, 'draft');
    }
    public function draftForumTopic(): HasMany
    {
        return $this->hasMany(TlForumTopicForumTopic::class, 'draft');
    }
    public function draftMonoForumDialog(): HasMany
    {
        return $this->hasMany(TlSavedDialogMonoForumDialog::class, 'draft');
    }
    public function draftUpdateDraftMessage(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateDraftMessage::class, 'draft');
    }
}
