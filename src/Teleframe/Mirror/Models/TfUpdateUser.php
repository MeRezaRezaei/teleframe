<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_updates_user. 1:1 user_id of a user-scoped update.
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $seq
 * @property int $position
 * @property int $user_id
 */
final class TfUpdateUser extends MirrorChildModel
{
    protected $table = 'tf_updates_user';

    /** @var list<string> */
    protected $fillable = ['account_id', 'seq', 'position', 'user_id'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'seq' => 'int', 'position' => 'int', 'user_id' => 'int'];
}
