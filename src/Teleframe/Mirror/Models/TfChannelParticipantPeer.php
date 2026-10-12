<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_channel_participants_peer. 1:1 exact banned/left peer pair.
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $channel_id
 * @property int $user_id
 * @property int $peer_type
 * @property int $peer_id
 */
final class TfChannelParticipantPeer extends MirrorChildModel
{
    protected $table = 'tf_channel_participants_peer';

    /** @var list<string> */
    protected $fillable = ['account_id', 'channel_id', 'user_id', 'peer_type', 'peer_id'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'channel_id' => 'int', 'user_id' => 'int', 'peer_type' => 'int', 'peer_id' => 'int'];
}
