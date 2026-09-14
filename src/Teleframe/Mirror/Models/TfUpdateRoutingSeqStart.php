<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_update_routing_seq_start. 1:1 updatesCombined seq_start for a routing receipt.
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $seq
 * @property int $position
 * @property int $seq_start
 */
final class TfUpdateRoutingSeqStart extends MirrorChildModel
{
    protected $table = 'tf_update_routing_seq_start';

    /** @var list<string> */
    protected $fillable = ['account_id', 'seq', 'position', 'seq_start'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'seq' => 'int', 'position' => 'int', 'seq_start' => 'int'];
}
