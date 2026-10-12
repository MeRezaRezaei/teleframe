<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_updates_status. 1:1 UserStatus union mirror (updateUserStatus.status).
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $seq
 * @property int $position
 * @property string $constructor
 * @property bool $by_me
 * @property int $expires
 * @property int $was_online
 */
final class TfUpdateStatus extends MirrorChildModel
{
    protected $table = 'tf_updates_status';

    /** @var list<string> */
    protected $fillable = ['account_id', 'seq', 'position', 'constructor', 'by_me', 'expires', 'was_online'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'seq' => 'int', 'position' => 'int', 'constructor' => 'string', 'by_me' => 'bool', 'expires' => 'int', 'was_online' => 'int'];
}
