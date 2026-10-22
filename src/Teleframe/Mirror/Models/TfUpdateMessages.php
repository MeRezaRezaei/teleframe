<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_updates_messages. 1:N updateDeleteMessages Vector<int>, keyed by vector slot.
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $seq
 * @property int $position
 * @property int $message_position
 * @property int $message_id
 */
final class TfUpdateMessages extends MirrorChildModel
{
    protected $table = 'tf_updates_messages';

    /** @var list<string> */
    protected $fillable = ['account_id', 'seq', 'position', 'message_position', 'message_id'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'seq' => 'int', 'position' => 'int', 'message_position' => 'int', 'message_id' => 'int'];
}
