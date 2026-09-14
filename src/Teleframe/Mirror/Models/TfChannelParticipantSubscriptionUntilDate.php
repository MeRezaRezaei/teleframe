<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_channel_participants_subscription_until_date. 1:1 subscription_until_date.
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $channel_id
 * @property int $user_id
 * @property int $subscription_until_date
 */
final class TfChannelParticipantSubscriptionUntilDate extends MirrorChildModel
{
    protected $table = 'tf_channel_participants_subscription_until_date';

    /** @var list<string> */
    protected $fillable = ['account_id', 'channel_id', 'user_id', 'subscription_until_date'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'channel_id' => 'int', 'user_id' => 'int', 'subscription_until_date' => 'int'];
}
