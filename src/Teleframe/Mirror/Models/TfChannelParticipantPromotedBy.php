<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_channel_participants_promoted_by. 1:1 promoted_by (admin).
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $channel_id
 * @property int $user_id
 * @property int $promoted_by
 */
final class TfChannelParticipantPromotedBy extends MirrorChildModel
{
    protected $table = 'tf_channel_participants_promoted_by';

    /** @var list<string> */
    protected $fillable = ['account_id', 'channel_id', 'user_id', 'promoted_by'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'channel_id' => 'int', 'user_id' => 'int', 'promoted_by' => 'int'];
}
