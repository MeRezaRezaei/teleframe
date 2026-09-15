<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * tf_users_last_name — 1:1 fact of TfUser (user.last_name, flags.2?string).
 *
 * @property int $account_id
 * @property int $id
 * @property string $last_name
 */
final class TfUsersLastName extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_users_last_name';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
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
