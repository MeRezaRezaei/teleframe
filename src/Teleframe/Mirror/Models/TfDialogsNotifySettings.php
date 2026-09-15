<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Builder;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;

/**
 * tf_dialogs_notify_settings — 1:1 object child of TfDialog
 * (dialog.notify_settings, flags.0?PeerNotifySettings). Single-ctor
 * PeerNotifySettings, constructor-discriminated; the NotificationSound
 * objects (sound / other / ios_sound / android_sound / _vibrate) are nested
 * and deferred to the flat write path.
 *
 * @property int $account_id
 * @property int $peer_type
 * @property int $peer_id
 * @property string $constructor
 * @property bool $show_previews
 * @property bool $silent
 * @property int $mute_until
 * @property bool $stories_muted
 * @property bool $stories_hide_sender
 */
final class TfDialogsNotifySettings extends MirrorChildModel
{
    use AccountScoped;

    protected $table = 'tf_dialogs_notify_settings';

    protected $primaryKey = 'account_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'peer_type' => 'integer',
        'peer_id' => 'integer',
        'show_previews' => 'boolean',
        'silent' => 'boolean',
        'mute_until' => 'integer',
        'stories_muted' => 'boolean',
        'stories_hide_sender' => 'boolean',
    ];

    /**
     * @return array<string, int>
     */
    public function childKey(): array
    {
        return [
            'account_id' => (int) $this->account_id,
            'peer_type' => (int) $this->peer_type,
            'peer_id' => (int) $this->peer_id,
        ];
    }

    public function dialog(): Builder
    {
        return TfDialog::query()
            ->where('account_id', (int) $this->account_id)
            ->where('peer_type', (int) $this->peer_type)
            ->where('peer_id', (int) $this->peer_id);
    }
}
