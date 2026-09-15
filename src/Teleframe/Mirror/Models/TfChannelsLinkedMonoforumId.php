<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * tf_channels_linked_monoforum_id — 1:1 fact of TfChannel
 * (channel.linked_monoforum_id, flags.28?long).
 *
 * @property int $account_id
 * @property int $id
 * @property int $linked_monoforum_id
 */
final class TfChannelsLinkedMonoforumId extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_channels_linked_monoforum_id';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'linked_monoforum_id' => 'integer',
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
