<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Builder;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Hand-authored NF5 mirror of the ChannelParticipant union — table
 * tf_channel_participants. user_id also carries the peer's user id for the
 * peer-carrying ctors (channelParticipantBanned / Left; the exact pair lives
 * in TfChannelParticipantPeer). date defaults to 0 for channelParticipantCreator.
 *
 * inviter_id, promoted_by and kicked_by are COLUMNS here and were TABLES until
 * 2026-10-08. `channelParticipantSelf#a9478a1a … inviter_id:long`,
 * `channelParticipantAdmin#34c3bb53 … promoted_by:long` and
 * `channelParticipantBanned#d5f0ad91 … kicked_by:long` are each a bare `long` on one
 * constructor, so the wire nests nothing there and a child table asserted an object
 * that does not exist — audit row C5, three times over. They follow the same
 * convention `date` already uses: 0 means "this constructor declares no such field".
 *
 * @property int $account_id
 * @property int $channel_id
 * @property int $user_id
 * @property string $constructor
 * @property int $date
 * @property bool $via_request
 * @property bool $is_self
 * @property bool $can_edit
 * @property bool $is_left
 * @property int $inviter_id
 * @property int $kicked_by
 * @property int $promoted_by
 */
final class TfChannelParticipant extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_channel_participants';

    protected $primaryKey = 'account_id';

    /** @var list<string> */
    protected $fillable = [
        'account_id',
        'channel_id',
        'user_id',
        'constructor',
        'date',
        'via_request',
        'is_self',
        'can_edit',
        'is_left',
        'inviter_id',
        'kicked_by',
        'promoted_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'account_id' => 'int',
        'channel_id' => 'int',
        'user_id' => 'int',
        'date' => 'int',
        'via_request' => 'bool',
        'is_self' => 'bool',
        'can_edit' => 'bool',
        'is_left' => 'bool',
        'inviter_id' => 'int',
        'kicked_by' => 'int',
        'promoted_by' => 'int',
    ];

    /**
     * @return array<string, int>
     */
    public function childKey(): array
    {
        return [
            'account_id' => (int) $this->account_id,
            'channel_id' => (int) $this->channel_id,
            'user_id' => (int) $this->user_id,
        ];
    }

    /**
     * @param  class-string<MirrorChildModel>  $child
     */
    private function child(string $child): Builder
    {
        return $child::query()
            ->where('account_id', (int) $this->account_id)
            ->where('channel_id', (int) $this->channel_id)
            ->where('user_id', (int) $this->user_id);
    }

    public function peer(): Builder
    {
        return $this->child(TfChannelParticipantPeer::class);
    }

    public function subscriptionUntilDate(): Builder
    {
        return $this->child(TfChannelParticipantSubscriptionUntilDate::class);
    }

    public function rank(): Builder
    {
        return $this->child(TfChannelParticipantRank::class);
    }
}
