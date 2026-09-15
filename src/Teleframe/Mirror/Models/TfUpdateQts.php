<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_updates_qts. 1:1 qts of an encrypted/message-media update.
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $seq
 * @property int $position
 * @property int $qts
 */
final class TfUpdateQts extends MirrorChildModel
{
    protected $table = 'tf_updates_qts';

    /** @var list<string> */
    protected $fillable = ['account_id', 'seq', 'position', 'qts'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'seq' => 'int', 'position' => 'int', 'qts' => 'int'];
}
