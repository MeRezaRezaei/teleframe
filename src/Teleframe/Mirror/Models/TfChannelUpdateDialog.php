<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_channel_updates_dialog. 1:1 pinned dialog (peer pair + top_message) of channelDifferenceTooLong.
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $channel_id
 * @property int $position
 * @property int $peer_type
 * @property int $peer_id
 * @property int $top_message
 */
final class TfChannelUpdateDialog extends MirrorChildModel
{
    protected $table = 'tf_channel_updates_dialog';

    /** @var list<string> */
    protected $fillable = ['account_id', 'channel_id', 'position', 'peer_type', 'peer_id', 'top_message'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'channel_id' => 'int', 'position' => 'int', 'peer_type' => 'int', 'peer_id' => 'int', 'top_message' => 'int'];
}
