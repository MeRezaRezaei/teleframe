<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_updates_from. 1:1 from_id peer pair (typing/participant updates).
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $seq
 * @property int $position
 * @property int $peer_type
 * @property int $peer_id
 */
final class TfUpdateFrom extends MirrorChildModel
{
    protected $table = 'tf_updates_from';

    /** @var list<string> */
    protected $fillable = ['account_id', 'seq', 'position', 'peer_type', 'peer_id'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'seq' => 'int', 'position' => 'int', 'peer_type' => 'int', 'peer_id' => 'int'];
}
