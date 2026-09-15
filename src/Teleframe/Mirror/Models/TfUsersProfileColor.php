<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * tf_users_profile_color — 1:1 object child of TfUser
 * (user.profile_color, flags2.3?PeerColor). Single-ctor PeerColor,
 * constructor-discriminated; the internal colors vectors are deferred to
 * the flat write path.
 *
 * @property int $account_id
 * @property int $id
 * @property string $constructor
 * @property int $color
 * @property int $background_emoji_id
 * @property int $collectible_id
 * @property int $gift_emoji_id
 * @property int $accent_color
 * @property int $dark_accent_color
 */
final class TfUsersProfileColor extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_users_profile_color';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'color' => 'integer',
        'background_emoji_id' => 'integer',
        'collectible_id' => 'integer',
        'gift_emoji_id' => 'integer',
        'accent_color' => 'integer',
        'dark_accent_color' => 'integer',
    ];

    /**
     * @return array<string, int>
     */
    public function childKey(): array
    {
        return [
            'account_id' => (int) $this->account_id,
            'id' => (int) $this->id,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(TfUser::class, 'id', 'id');
    }
}
