<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_updates_chat. 1:1 chat_id of a group-chat-scoped update.
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $seq
 * @property int $position
 * @property int $chat_id
 */
final class TfUpdateChat extends MirrorChildModel
{
    protected $table = 'tf_updates_chat';

    /** @var list<string> */
    protected $fillable = ['account_id', 'seq', 'position', 'chat_id'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'seq' => 'int', 'position' => 'int', 'chat_id' => 'int'];
}
