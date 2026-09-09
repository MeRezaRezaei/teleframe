<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageMediaInvoice;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineResultInputBotInlineResult;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaInvoice;

/** Anchor model for TL type InputWebDocument (spec §4.1). */
final class TlInputWebDocument extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_web_document';

    protected $guarded = [];

    public function content(): HasMany
    {
        return $this->hasMany(TlInputBotInlineResultInputBotInlineResult::class, 'content');
    }
    public function photo(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaInvoice::class, 'photo');
    }
    public function photoInputBotInlineMessageMediaInvoice(): HasMany
    {
        return $this->hasMany(TlInputBotInlineMessageInputBotInlineMessageMediaInvoice::class, 'photo');
    }
    public function thumb(): HasMany
    {
        return $this->hasMany(TlInputBotInlineResultInputBotInlineResult::class, 'thumb');
    }
}
