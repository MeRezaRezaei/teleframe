<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * tf_chats_migrated_to — 1:1 object child of TfChat (chat.migrated_to,
 * flags.6?InputChannel). InputChannel union (inputChannelEmpty /
 * inputChannel / inputChannelFromMessage), constructor-discriminated. The
 * FromMessage variant's peer expands to a canonical peer pair (peer_type /
 * peer_id) with a flat msg_id.
 *
 * @property int $account_id
 * @property int $id
 * @property string $constructor
 * @property int $channel_id
 * @property int $access_hash
 * @property int $peer_type
 * @property int $peer_id
 * @property int $msg_id
 */
final class TfChatsMigratedTo extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_chats_migrated_to';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'channel_id' => 'integer',
        'access_hash' => 'integer',
        'peer_type' => 'integer',
        'peer_id' => 'integer',
        'msg_id' => 'integer',
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
