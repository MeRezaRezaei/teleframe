<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * tf_chats_admin_rights — 1:1 object child of TfChat (chat.admin_rights,
 * flags.8?ChatAdminRights). Single-ctor ChatAdminRights, constructor-
 * discriminated; every right is a flags.?true boolean.
 *
 * @property int $account_id
 * @property int $id
 * @property string $constructor
 * @property bool $change_info
 * @property bool $post_messages
 * @property bool $edit_messages
 * @property bool $delete_messages
 * @property bool $ban_users
 * @property bool $invite_users
 * @property bool $pin_messages
 * @property bool $add_admins
 * @property bool $anonymous
 * @property bool $manage_call
 * @property bool $other
 * @property bool $manage_topics
 * @property bool $post_stories
 * @property bool $edit_stories
 * @property bool $delete_stories
 * @property bool $manage_direct_messages
 * @property bool $manage_ranks
 */
final class TfChatsAdminRights extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_chats_admin_rights';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'change_info' => 'boolean',
        'post_messages' => 'boolean',
        'edit_messages' => 'boolean',
        'delete_messages' => 'boolean',
        'ban_users' => 'boolean',
        'invite_users' => 'boolean',
        'pin_messages' => 'boolean',
        'add_admins' => 'boolean',
        'anonymous' => 'boolean',
        'manage_call' => 'boolean',
        'other' => 'boolean',
        'manage_topics' => 'boolean',
        'post_stories' => 'boolean',
        'edit_stories' => 'boolean',
        'delete_stories' => 'boolean',
        'manage_direct_messages' => 'boolean',
        'manage_ranks' => 'boolean',
    ];

    /**
     * @return array<string, int>
     */
    public function childKey(): array
    {
        return [
            'account_id' => (int) $this->account_id,
            'id' => (int) $this->id,
        ];
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(TfChat::class, 'id', 'id');
    }
}
