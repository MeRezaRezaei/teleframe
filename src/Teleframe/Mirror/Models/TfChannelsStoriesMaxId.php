<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * tf_channels_stories_max_id — 1:1 object child of TfChannel
 * (channel.stories_max_id, flags.25?RecentStory). Single-ctor recentStory,
 * constructor-discriminated.
 *
 * @property int $account_id
 * @property int $id
 * @property string $constructor
 * @property bool $live
 * @property int $max_id
 */
final class TfChannelsStoriesMaxId extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_channels_stories_max_id';

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

    public function channel(): BelongsTo
    {
        return $this->belongsTo(TfChannel::class, 'id', 'id');
    }
}
