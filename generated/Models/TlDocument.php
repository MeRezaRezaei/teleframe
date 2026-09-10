<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountSavedRingtoneSavedRingtoneConverted;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAttachMenuBotIconAttachMenuBotIcon;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAvailableReactionAvailableReaction;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotAppBotApp;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInfoBotInfo;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineResultBotInlineMediaResult;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessIntroBusinessIntro;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGameGame;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpAppUpdateAppUpdate;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaPhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAttributeStarGiftAttributeModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAttributeStarGiftAttributePattern;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftCollectionStarGiftCollection;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftStarGift;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSetCoveredStickerSetCovered;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryAlbumStoryAlbum;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryItemStoryItem;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlThemeTheme;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserFullUserFull;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWallPaperWallPaper;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebPageWebPage;

/** Anchor model for TL type Document (spec §4.1). */
final class TlDocument extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_document_document';

    protected $guarded = [];

    public function activateAnimation(): HasMany
    {
        return $this->hasMany(TlAvailableReactionAvailableReaction::class, 'activate_animation');
    }
    public function appearAnimation(): HasMany
    {
        return $this->hasMany(TlAvailableReactionAvailableReaction::class, 'appear_animation');
    }
    public function aroundAnimation(): HasMany
    {
        return $this->hasMany(TlAvailableReactionAvailableReaction::class, 'around_animation');
    }
    public function centerIcon(): HasMany
    {
        return $this->hasMany(TlAvailableReactionAvailableReaction::class, 'center_icon');
    }
    public function cover(): HasMany
    {
        return $this->hasMany(TlStickerSetCoveredStickerSetCovered::class, 'cover');
    }
    public function descriptionDocument(): HasMany
    {
        return $this->hasMany(TlBotInfoBotInfo::class, 'description_document');
    }
    public function document(): HasMany
    {
        return $this->hasMany(TlMessageMediaMessageMediaDocument::class, 'document');
    }
    public function documentAccountSavedRingtoneConverted(): HasMany
    {
        return $this->hasMany(TlAccountSavedRingtoneSavedRingtoneConverted::class, 'document');
    }
    public function documentBotApp(): HasMany
    {
        return $this->hasMany(TlBotAppBotApp::class, 'document');
    }
    public function documentBotInlineMediaResult(): HasMany
    {
        return $this->hasMany(TlBotInlineResultBotInlineMediaResult::class, 'document');
    }
    public function documentGame(): HasMany
    {
        return $this->hasMany(TlGameGame::class, 'document');
    }
    public function documentHelpAppUpdate(): HasMany
    {
        return $this->hasMany(TlHelpAppUpdateAppUpdate::class, 'document');
    }
    public function documentStarGiftAttributeModel(): HasMany
    {
        return $this->hasMany(TlStarGiftAttributeStarGiftAttributeModel::class, 'document');
    }
    public function documentStarGiftAttributePattern(): HasMany
    {
        return $this->hasMany(TlStarGiftAttributeStarGiftAttributePattern::class, 'document');
    }
    public function documentTheme(): HasMany
    {
        return $this->hasMany(TlThemeTheme::class, 'document');
    }
    public function documentWallPaper(): HasMany
    {
        return $this->hasMany(TlWallPaperWallPaper::class, 'document');
    }
    public function documentWebPage(): HasMany
    {
        return $this->hasMany(TlWebPageWebPage::class, 'document');
    }
    public function effectAnimation(): HasMany
    {
        return $this->hasMany(TlAvailableReactionAvailableReaction::class, 'effect_animation');
    }
    public function icon(): HasMany
    {
        return $this->hasMany(TlAttachMenuBotIconAttachMenuBotIcon::class, 'icon');
    }
    public function iconStarGiftCollection(): HasMany
    {
        return $this->hasMany(TlStarGiftCollectionStarGiftCollection::class, 'icon');
    }
    public function iconVideo(): HasMany
    {
        return $this->hasMany(TlStoryAlbumStoryAlbum::class, 'icon_video');
    }
    public function music(): HasMany
    {
        return $this->hasMany(TlStoryItemStoryItem::class, 'music');
    }
    public function savedMusic(): HasMany
    {
        return $this->hasMany(TlUserFullUserFull::class, 'saved_music');
    }
    public function selectAnimation(): HasMany
    {
        return $this->hasMany(TlAvailableReactionAvailableReaction::class, 'select_animation');
    }
    public function staticIcon(): HasMany
    {
        return $this->hasMany(TlAvailableReactionAvailableReaction::class, 'static_icon');
    }
    public function sticker(): HasMany
    {
        return $this->hasMany(TlHelpAppUpdateAppUpdate::class, 'sticker');
    }
    public function stickerBusinessIntro(): HasMany
    {
        return $this->hasMany(TlBusinessIntroBusinessIntro::class, 'sticker');
    }
    public function stickerStarGift(): HasMany
    {
        return $this->hasMany(TlStarGiftStarGift::class, 'sticker');
    }
    public function video(): HasMany
    {
        return $this->hasMany(TlMessageMediaMessageMediaPhoto::class, 'video');
    }
}
