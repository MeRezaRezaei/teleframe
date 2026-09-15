<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * tf_channels_default_banned_rights — 1:1 object child of TfChannel
 * (channel.default_banned_rights, flags.13?ChatBannedRights). Single-ctor
 * ChatBannedRights, constructor-discriminated; every right is a flags.?true
 * boolean, until_date the single non-flag int.
 *
 * @property int $account_id
 * @property int $id
 * @property string $constructor
 * @property bool $view_messages
 * @property bool $send_messages
 * @property bool $send_media
 * @property bool $send_stickers
 * @property bool $send_gifs
 * @property bool $send_games
 * @property bool $send_inline
 * @property bool $embed_links
 * @property bool $send_polls
 * @property bool $change_info
 * @property bool $invite_users
 * @property bool $pin_messages
 * @property bool $manage_topics
 * @property bool $send_photos
 * @property bool $send_videos
 * @property bool $send_roundvideos
 * @property bool $send_audios
 * @property bool $send_voices
 * @property bool $send_docs
 * @property bool $send_plain
 * @property bool $edit_rank
 * @property bool $send_reactions
 * @property int $until_date
 */
final class TfChannelsDefaultBannedRights extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_channels_default_banned_rights';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'view_messages' => 'boolean',
        'send_messages' => 'boolean',
        'send_media' => 'boolean',
        'send_stickers' => 'boolean',
        'send_gifs' => 'boolean',
        'send_games' => 'boolean',
        'send_inline' => 'boolean',
        'embed_links' => 'boolean',
        'send_polls' => 'boolean',
        'change_info' => 'boolean',
        'invite_users' => 'boolean',
        'pin_messages' => 'boolean',
        'manage_topics' => 'boolean',
        'send_photos' => 'boolean',
        'send_videos' => 'boolean',
        'send_roundvideos' => 'boolean',
        'send_audios' => 'boolean',
        'send_voices' => 'boolean',
        'send_docs' => 'boolean',
        'send_plain' => 'boolean',
        'edit_rank' => 'boolean',
        'send_reactions' => 'boolean',
        'until_date' => 'integer',
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

    public function channel(): BelongsTo
    {
        return $this->belongsTo(TfChannel::class, 'id', 'id');
    }
}
