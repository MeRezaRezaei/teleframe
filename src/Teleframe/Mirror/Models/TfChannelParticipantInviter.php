<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_channel_participants_inviter. 1:1 inviter_id (self/admin).
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $channel_id
 * @property int $user_id
 * @property int $inviter_id
 */
final class TfChannelParticipantInviter extends MirrorChildModel
{
    protected $table = 'tf_channel_participants_inviter';

    /** @var list<string> */
    protected $fillable = ['account_id', 'channel_id', 'user_id', 'inviter_id'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'channel_id' => 'int', 'user_id' => 'int', 'inviter_id' => 'int'];
}
