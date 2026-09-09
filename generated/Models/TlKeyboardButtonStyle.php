<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonInputKeyboardButtonRequestPeer;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonInputKeyboardButtonUrlAuth;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonInputKeyboardButtonUserProfile;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButton;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButtonBuy;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButtonCallback;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButtonCopy;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButtonGame;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButtonRequestGeoLocation;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButtonRequestPeer;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButtonRequestPhone;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButtonRequestPoll;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButtonSimpleWebView;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButtonSwitchInline;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButtonUrl;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButtonUrlAuth;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButtonUserProfile;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButtonWebView;

/** Anchor model for TL type KeyboardButtonStyle (spec §4.1). */
final class TlKeyboardButtonStyle extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_keyboard_button_style';

    protected $guarded = [];

    public function style(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonKeyboardButton::class, 'style');
    }
    public function styleInputKeyboardButtonRequestPeer(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonInputKeyboardButtonRequestPeer::class, 'style');
    }
    public function styleInputKeyboardButtonUrlAuth(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonInputKeyboardButtonUrlAuth::class, 'style');
    }
    public function styleInputKeyboardButtonUserProfile(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonInputKeyboardButtonUserProfile::class, 'style');
    }
    public function styleKeyboardButtonBuy(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonKeyboardButtonBuy::class, 'style');
    }
    public function styleKeyboardButtonCallback(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonKeyboardButtonCallback::class, 'style');
    }
    public function styleKeyboardButtonCopy(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonKeyboardButtonCopy::class, 'style');
    }
    public function styleKeyboardButtonGame(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonKeyboardButtonGame::class, 'style');
    }
    public function styleKeyboardButtonRequestGeoLocation(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonKeyboardButtonRequestGeoLocation::class, 'style');
    }
    public function styleKeyboardButtonRequestPeer(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonKeyboardButtonRequestPeer::class, 'style');
    }
    public function styleKeyboardButtonRequestPhone(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonKeyboardButtonRequestPhone::class, 'style');
    }
    public function styleKeyboardButtonRequestPoll(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonKeyboardButtonRequestPoll::class, 'style');
    }
    public function styleKeyboardButtonSimpleWebView(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonKeyboardButtonSimpleWebView::class, 'style');
    }
    public function styleKeyboardButtonSwitchInline(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonKeyboardButtonSwitchInline::class, 'style');
    }
    public function styleKeyboardButtonUrl(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonKeyboardButtonUrl::class, 'style');
    }
    public function styleKeyboardButtonUrlAuth(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonKeyboardButtonUrlAuth::class, 'style');
    }
    public function styleKeyboardButtonUserProfile(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonKeyboardButtonUserProfile::class, 'style');
    }
    public function styleKeyboardButtonWebView(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonKeyboardButtonWebView::class, 'style');
    }
}
