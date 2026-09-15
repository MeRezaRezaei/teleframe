<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * tf_channels_bot_verification_icon — 1:1 fact of TfChannel
 * (channel.bot_verification_icon, flags.27?long).
 *
 * @property int $account_id
 * @property int $id
 * @property int $bot_verification_icon
 */
final class TfChannelsBotVerificationIcon extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_channels_bot_verification_icon';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'bot_verification_icon' => 'integer',
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
