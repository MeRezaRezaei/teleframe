<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_updates_message. 1:1 embedded-message link (the update's peer pair + native message id).
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $seq
 * @property int $position
 * @property int $peer_type
 * @property int $peer_id
 * @property int $message_id
 */
final class TfUpdateMessage extends MirrorChildModel
{
    protected $table = 'tf_updates_message';

    /** @var list<string> */
    protected $fillable = ['account_id', 'seq', 'position', 'peer_type', 'peer_id', 'message_id'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'seq' => 'int', 'position' => 'int', 'peer_type' => 'int', 'peer_id' => 'int', 'message_id' => 'int'];
}
