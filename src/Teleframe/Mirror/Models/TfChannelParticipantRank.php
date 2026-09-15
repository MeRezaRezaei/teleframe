<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_channel_participants_rank. 1:1 rank (flags.2).
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $channel_id
 * @property int $user_id
 * @property string $rank
 */
final class TfChannelParticipantRank extends MirrorChildModel
{
    protected $table = 'tf_channel_participants_rank';

    /** @var list<string> */
    protected $fillable = ['account_id', 'channel_id', 'user_id', 'rank'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'channel_id' => 'int', 'user_id' => 'int', 'rank' => 'string'];
}
