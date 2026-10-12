<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Builder;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Hand-authored NF5 mirror of the updates.ChannelDifference union — table
 * tf_channel_updates, one row per channel-diff response; position
 * disambiguates repeated forced snapshots for the same channel.
 *
 * @property int $account_id
 * @property int $channel_id
 * @property int $position
 * @property string $constructor
 * @property bool $is_final
 */
final class TfChannelUpdate extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_channel_updates';

    protected $primaryKey = 'account_id';

    /** @var list<string> */
    protected $fillable = ['account_id', 'channel_id', 'position', 'constructor', 'is_final'];

    /** @var array<string, string> */
    protected $casts = [
        'account_id' => 'int',
        'channel_id' => 'int',
        'position' => 'int',
        'is_final' => 'bool',
    ];

    /**
     * @return array<string, int>
     */
    public function childKey(): array
    {
        return [
            'account_id' => (int) $this->account_id,
            'channel_id' => (int) $this->channel_id,
            'position' => (int) $this->position,
        ];
    }

    /**
     * @param  class-string<MirrorChildModel>  $child
     */
    private function child(string $child): Builder
    {
        return $child::query()
            ->where('account_id', (int) $this->account_id)
            ->where('channel_id', (int) $this->channel_id)
            ->where('position', (int) $this->position);
    }

    public function pts(): Builder
    {
        return $this->child(TfChannelUpdatePts::class);
    }

    public function timeout(): Builder
    {
        return $this->child(TfChannelUpdateTimeout::class);
    }

    public function dialog(): Builder
    {
        return $this->child(TfChannelUpdateDialog::class);
    }
}
