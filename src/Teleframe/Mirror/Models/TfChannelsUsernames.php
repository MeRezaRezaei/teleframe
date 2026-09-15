<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * tf_channels_usernames — 1:N positioned child of TfChannel
 * (channel.usernames, flags.20?Vector<Username>). Each Username element is
 * one positioned row, constructor-discriminated.
 *
 * @property int $account_id
 * @property int $id
 * @property int $position
 * @property string $constructor
 * @property bool $editable
 * @property bool $active
 * @property string $username
 */
final class TfChannelsUsernames extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_channels_usernames';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'position' => 'integer',
        'editable' => 'boolean',
        'active' => 'boolean',
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
