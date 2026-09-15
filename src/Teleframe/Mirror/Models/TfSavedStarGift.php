<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * NF5 mirror of the savedStarGift ctor — table tf_saved_star_gifts.
 *
 * The TL ctor carries no own id; the parent key id IS the inner StarGift id
 * (long). gift is the required StarGift union flattened in the 1:1 gift child
 * (its inner id is the parent key — dropped under the flat path).
 *
 * @property int $account_id
 * @property int $id
 * @property int $date
 */
final class TfSavedStarGift extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_saved_star_gifts';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'int',
        'id' => 'int',
        'date' => 'int',
        'name_hidden' => 'bool',
        'unsaved' => 'bool',
        'refunded' => 'bool',
        'can_upgrade' => 'bool',
        'pinned_to_top' => 'bool',
        'upgrade_separate' => 'bool',
    ];

    public function gift(): HasOne
    {
        return $this->hasOne(TfSavedStarGiftGift::class, 'id', 'id');
    }

    public function fromId(): HasOne
    {
        return $this->hasOne(TfSavedStarGiftFromId::class, 'id', 'id');
    }

    public function message(): HasOne
    {
        return $this->hasOne(TfSavedStarGiftMessage::class, 'id', 'id');
    }

    public function msgId(): HasOne
    {
        return $this->hasOne(TfSavedStarGiftMsgId::class, 'id', 'id');
    }

    public function savedId(): HasOne
    {
        return $this->hasOne(TfSavedStarGiftSavedId::class, 'id', 'id');
    }

    public function convertStars(): HasOne
    {
        return $this->hasOne(TfSavedStarGiftConvertStars::class, 'id', 'id');
    }

    public function upgradeStars(): HasOne
    {
        return $this->hasOne(TfSavedStarGiftUpgradeStars::class, 'id', 'id');
    }

    public function canExportAt(): HasOne
    {
        return $this->hasOne(TfSavedStarGiftCanExportAt::class, 'id', 'id');
    }

    public function transferStars(): HasOne
    {
        return $this->hasOne(TfSavedStarGiftTransferStars::class, 'id', 'id');
    }

    public function canTransferAt(): HasOne
    {
        return $this->hasOne(TfSavedStarGiftCanTransferAt::class, 'id', 'id');
    }

    public function canResellAt(): HasOne
    {
        return $this->hasOne(TfSavedStarGiftCanResellAt::class, 'id', 'id');
    }

    /** 1:N — the Vector<int> of collection_id; caller orders by slot. */
    public function collectionIds(): HasMany
    {
        return $this->hasMany(TfSavedStarGiftCollectionId::class, 'id', 'id');
    }

    public function prepaidUpgradeHash(): HasOne
    {
        return $this->hasOne(TfSavedStarGiftPrepaidUpgradeHash::class, 'id', 'id');
    }

    public function dropOriginalDetailsStars(): HasOne
    {
        return $this->hasOne(TfSavedStarGiftDropOriginalDetailsStars::class, 'id', 'id');
    }

    public function giftNum(): HasOne
    {
        return $this->hasOne(TfSavedStarGiftGiftNum::class, 'id', 'id');
    }

    public function canCraftAt(): HasOne
    {
        return $this->hasOne(TfSavedStarGiftCanCraftAt::class, 'id', 'id');
    }

    /**
     * The full composite key of this row, for writing a child fact.
     *
     * @return array<string, int>
     */
    public function childKey(): array
    {
        return [
            'account_id' => (int) $this->account_id,
            'id' => (int) $this->id,
        ];
    }
}
