<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * tf_channels_level — 1:1 fact of TfChannel (channel.level, flags.18?int).
 *
 * @property int $account_id
 * @property int $id
 * @property int $level
 */
final class TfChannelsLevel extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_channels_level';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'level' => 'integer',
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
