<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * tf_users_photo — 1:1 object child of TfUser (user.photo,
 * flags.5?UserProfilePhoto). UserProfilePhoto union, constructor-
 * discriminated; stripped_thumb is wire bytes → lowercase hex TEXT.
 *
 * @property int $account_id
 * @property int $id
 * @property string $constructor
 * @property bool $has_video
 * @property bool $personal
 * @property int $photo_id
 * @property string $stripped_thumb
 * @property int $dc_id
 */
final class TfUsersPhoto extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_users_photo';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'has_video' => 'boolean',
        'personal' => 'boolean',
        'photo_id' => 'integer',
        'dc_id' => 'integer',
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
