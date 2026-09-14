<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_updates_channel. 1:1 channel_id of a channel-scoped update.
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $seq
 * @property int $position
 * @property int $channel_id
 */
final class TfUpdateChannel extends MirrorChildModel
{
    protected $table = 'tf_updates_channel';

    /** @var list<string> */
    protected $fillable = ['account_id', 'seq', 'position', 'channel_id'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'seq' => 'int', 'position' => 'int', 'channel_id' => 'int'];
}
