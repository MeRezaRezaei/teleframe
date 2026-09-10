<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDraftMessageDraftMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaInvoice;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaPoll;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputSingleMediaInputSingleMedia;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollAnswerInputPollAnswer;

/** Anchor model for TL type InputMedia (spec §4.1). */
final class TlInputMedia extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_media_input_media_contact';

    protected $guarded = [];

    public function attachedMedia(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaPoll::class, 'attached_media');
    }
    public function extendedMedia(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaInvoice::class, 'extended_media');
    }
    public function media(): HasMany
    {
        return $this->hasMany(TlDraftMessageDraftMessage::class, 'media');
    }
    public function mediaInputPollAnswer(): HasMany
    {
        return $this->hasMany(TlPollAnswerInputPollAnswer::class, 'media');
    }
    public function mediaInputSingleMedia(): HasMany
    {
        return $this->hasMany(TlInputSingleMediaInputSingleMedia::class, 'media');
    }
    public function solutionMedia(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaPoll::class, 'solution_media');
    }
}
