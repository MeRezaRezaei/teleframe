<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * NF5 mirror chat (Chat union: chatEmpty / chat ctor).
 *
 * Table tf_chats — the peer FK target for peer_type 2. Composite key
 * (account_id, id); optional wire facts live in tf_chats_* 1:1 children
 * (row existence = fact existence).
 *
 * Cross-domain relations and the Task-8 FK wiring are added once all peer
 * target tables land.
 *
 * @property int $account_id
 * @property int $id
 * @property string $constructor
 * @property string $title
 * @property int $participants_count
 * @property int $date
 * @property int $version
 * @property bool $creator
 * @property bool $left
 * @property bool $deactivated
 * @property bool $call_active
 * @property bool $call_not_empty
 * @property bool $noforwards
 */
final class TfChat extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_chats';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'participants_count' => 'integer',
        'date' => 'integer',
        'version' => 'integer',
        'creator' => 'boolean',
        'left' => 'boolean',
        'deactivated' => 'boolean',
        'call_active' => 'boolean',
        'call_not_empty' => 'boolean',
        'noforwards' => 'boolean',
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

    public function photo(): HasOne
    {
        return $this->hasOne(TfChatsPhoto::class, 'id', 'id');
    }

    public function migratedTo(): HasOne
    {
        return $this->hasOne(TfChatsMigratedTo::class, 'id', 'id');
    }

    public function adminRights(): HasOne
    {
        return $this->hasOne(TfChatsAdminRights::class, 'id', 'id');
    }

    public function defaultBannedRights(): HasOne
    {
        return $this->hasOne(TfChatsDefaultBannedRights::class, 'id', 'id');
    }
}
