<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * tf_users_stories_max_id — 1:1 object child of TfUser (user.stories_max_id,
 * flags2.5?RecentStory). Single-ctor recentStory, constructor-discriminated.
 *
 * @property int $account_id
 * @property int $id
 * @property string $constructor
 * @property bool $live
 * @property int $max_id
 */
final class TfUsersStoriesMaxId extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_users_stories_max_id';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'live' => 'boolean',
        'max_id' => 'integer',
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
