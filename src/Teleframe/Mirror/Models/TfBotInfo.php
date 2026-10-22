<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * NF5 mirror of the botInfo ctor — table tf_bot_infos.
 *
 * botInfo carries no own TL id; the parent key id is the bot's user id
 * (designer key). The ctor base is empty — every payload field is flag-gated,
 * so all nine facts are 1:1 / 1:N children of this row.
 *
 * @property int $account_id
 * @property int $id
 */
final class TfBotInfo extends TfMirrorModel
{
    use AccountScoped;
    use PinsDeclaredForeignKeyLegs;

    protected $table = 'tf_bot_infos';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'int',
        'id' => 'int',
        'has_preview_medias' => 'bool',
        'description' => 'string',
    ];

    public function userId(): HasOne
    {
        return $this->hasOne(TfBotInfoUserId::class, 'id', 'id');
    }

    /**
     * `description` is a COLUMN here, not a child relation.
     *
     * `botInfo#4d8a0299` declares `description:flags.1?string` — a plain
     * `string` scalar (`schema/sources/TL_telegram_v227.tl:697`). It was a
     * `hasOne` to a one-row-per-bot child table until
     * `2026_10_11_000100_fold_bot_infos_description_onto_the_bot_info_fact.php`
     * folded that fabricated scalar table onto this fact row. The value is now
     * read as `$botInfo->description`.
     *
     * @property string $description
     */
    public function descriptionPhoto(): HasOne
    {
        return $this->hasOne(TfBotInfoDescriptionPhoto::class, 'id', 'id');
    }

    public function descriptionDocument(): HasOne
    {
        return $this->hasOne(TfBotInfoDescriptionDocument::class, 'id', 'id');
    }

    /** 1:N — the required Vector<BotCommand>; caller orders by slot. */
    public function commands(): HasMany
    {
        return $this->hasMany(TfBotInfoCommand::class, 'id', 'id');
    }

    public function menuButton(): HasOne
    {
        return $this->hasOne(TfBotInfoMenuButton::class, 'id', 'id');
    }

    public function privacyPolicyUrl(): HasOne
    {
        return $this->hasOne(TfBotInfoPrivacyPolicyUrl::class, 'id', 'id');
    }

    public function appSettings(): HasOne
    {
        return $this->hasOne(TfBotInfoAppSettings::class, 'id', 'id');
    }

    public function verifierSettings(): HasOne
    {
        return $this->hasOne(TfBotInfoVerifierSettings::class, 'id', 'id');
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
