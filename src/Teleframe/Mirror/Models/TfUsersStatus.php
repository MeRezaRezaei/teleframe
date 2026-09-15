<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * tf_users_status — 1:1 object child of TfUser (user.status,
 * flags.6?UserStatus). UserStatus union (userStatusEmpty / userStatusOnline /
 * userStatusOffline / userStatusRecently / userStatusLastWeek /
 * userStatusLastMonth), constructor-discriminated.
 *
 * @property int $account_id
 * @property int $id
 * @property string $constructor
 * @property int $expires
 * @property int $was_online
 * @property bool $by_me
 */
final class TfUsersStatus extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_users_status';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'expires' => 'integer',
        'was_online' => 'integer',
        'by_me' => 'boolean',
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
