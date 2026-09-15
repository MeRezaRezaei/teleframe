<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * tf_channels_restriction_reason — 1:N positioned child of TfChannel
 * (channel.restriction_reason, flags.9?Vector<RestrictionReason>). Each
 * RestrictionReason element is one positioned row.
 *
 * @property int $account_id
 * @property int $id
 * @property int $position
 * @property string $platform
 * @property string $reason
 * @property string $text
 */
final class TfChannelsRestrictionReason extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_channels_restriction_reason';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'position' => 'integer',
    ];

    /**
     * @return array<string, int>
     */
    public function childKey(): array
    {
        return [
            'account_id' => (int) $this->account_id,
            'id' => (int) $this->id,
            'position' => (int) $this->position,
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(TfChannel::class, 'id', 'id');
    }
}
