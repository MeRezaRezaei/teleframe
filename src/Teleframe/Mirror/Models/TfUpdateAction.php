<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_updates_action. 1:1 SendMessageAction union mirror (scalar fields only).
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $seq
 * @property int $position
 * @property string $constructor
 * @property int $progress
 * @property string $emoticon
 * @property int $msg_id
 * @property int $random_id
 */
final class TfUpdateAction extends MirrorChildModel
{
    protected $table = 'tf_updates_action';

    /** @var list<string> */
    protected $fillable = ['account_id', 'seq', 'position', 'constructor', 'progress', 'emoticon', 'msg_id', 'random_id'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'seq' => 'int', 'position' => 'int', 'constructor' => 'string', 'progress' => 'int', 'emoticon' => 'string', 'msg_id' => 'int', 'random_id' => 'int'];
}
