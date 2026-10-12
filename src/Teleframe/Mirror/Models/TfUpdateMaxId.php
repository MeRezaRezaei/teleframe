<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_updates_max_id. 1:1 max_id of a read-history update.
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $seq
 * @property int $position
 * @property int $max_id
 */
final class TfUpdateMaxId extends MirrorChildModel
{
    protected $table = 'tf_updates_max_id';

    /** @var list<string> */
    protected $fillable = ['account_id', 'seq', 'position', 'max_id'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'seq' => 'int', 'position' => 'int', 'max_id' => 'int'];
}
