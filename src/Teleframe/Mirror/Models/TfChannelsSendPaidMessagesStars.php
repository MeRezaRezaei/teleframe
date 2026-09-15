<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * tf_channels_send_paid_messages_stars — 1:1 fact of TfChannel
 * (channel.send_paid_messages_stars, flags.21?long).
 *
 * @property int $account_id
 * @property int $id
 * @property int $send_paid_messages_stars
 */
final class TfChannelsSendPaidMessagesStars extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_channels_send_paid_messages_stars';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'send_paid_messages_stars' => 'integer',
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
