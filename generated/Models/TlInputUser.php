<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotCommandScopeBotCommandScopePeerUser;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotAppInputBotAppShortName;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputGameInputGameShortName;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputInvoiceInputInvoiceBusinessBotTransferStars;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputInvoiceInputInvoicePremiumGiftStars;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStorePaymentPurposeInputStorePaymentGiftPremium;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStorePaymentPurposeInputStorePaymentStarsGift;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonInputKeyboardButtonUrlAuth;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonInputKeyboardButtonUserProfile;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageEntityInputMessageEntityMentionName;

/** Anchor model for TL type InputUser (spec §4.1). */
final class TlInputUser extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_user_input_user';

    protected $guarded = [];

    public function bot(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonInputKeyboardButtonUrlAuth::class, 'bot');
    }
    public function botId(): HasMany
    {
        return $this->hasMany(TlInputGameInputGameShortName::class, 'bot_id');
    }
    public function botIdInputBotAppShortName(): HasMany
    {
        return $this->hasMany(TlInputBotAppInputBotAppShortName::class, 'bot_id');
    }
    public function botInputInvoiceBusinessBotTransferStars(): HasMany
    {
        return $this->hasMany(TlInputInvoiceInputInvoiceBusinessBotTransferStars::class, 'bot');
    }
    public function userId(): HasMany
    {
        return $this->hasMany(TlMessageEntityInputMessageEntityMentionName::class, 'user_id');
    }
    public function userIdBotCommandScopePeerUser(): HasMany
    {
        return $this->hasMany(TlBotCommandScopeBotCommandScopePeerUser::class, 'user_id');
    }
    public function userIdInputInvoicePremiumGiftStars(): HasMany
    {
        return $this->hasMany(TlInputInvoiceInputInvoicePremiumGiftStars::class, 'user_id');
    }
    public function userIdInputKeyboardButtonUserProfile(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonInputKeyboardButtonUserProfile::class, 'user_id');
    }
    public function userIdInputStorePaymentGiftPremium(): HasMany
    {
        return $this->hasMany(TlInputStorePaymentPurposeInputStorePaymentGiftPremium::class, 'user_id');
    }
    public function userIdInputStorePaymentStarsGift(): HasMany
    {
        return $this->hasMany(TlInputStorePaymentPurposeInputStorePaymentStarsGift::class, 'user_id');
    }
}
