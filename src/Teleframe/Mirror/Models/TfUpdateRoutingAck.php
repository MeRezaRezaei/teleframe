<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_update_routing_ack. 1:1 quick-ack disposition for a routing receipt.
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $seq
 * @property int $position
 * @property bool $acked
 * @property int $acked_at
 */
final class TfUpdateRoutingAck extends MirrorChildModel
{
    protected $table = 'tf_update_routing_ack';

    /** @var list<string> */
    protected $fillable = ['account_id', 'seq', 'position', 'acked', 'acked_at'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'seq' => 'int', 'position' => 'int', 'acked' => 'bool', 'acked_at' => 'int'];
}
