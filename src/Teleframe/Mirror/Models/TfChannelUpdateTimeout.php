<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_channel_updates_timeout. 1:1 timeout of a channel-diff receipt.
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $channel_id
 * @property int $position
 * @property int $timeout
 */
final class TfChannelUpdateTimeout extends MirrorChildModel
{
    protected $table = 'tf_channel_updates_timeout';

    /** @var list<string> */
    protected $fillable = ['account_id', 'channel_id', 'position', 'timeout'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'channel_id' => 'int', 'position' => 'int', 'timeout' => 'int'];
}
