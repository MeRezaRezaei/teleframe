<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * NF5 mirror of the attachMenuBot ctor — table tf_attach_menu_bots. Keyed by
 * the natural bot_id (catalog `base`.bot_id).
 *
 * @property int $account_id
 * @property int $bot_id
 * @property string $short_name
 */
final class TfAttachMenuBot extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_attach_menu_bots';

    protected $primaryKey = 'bot_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'int',
        'bot_id' => 'int',
        'inactive' => 'bool',
        'has_settings' => 'bool',
        'request_write_access' => 'bool',
        'show_in_attach_menu' => 'bool',
        'show_in_side_menu' => 'bool',
        'side_menu_disclaimer_needed' => 'bool',
    ];

    /** 1:N — the Vector<AttachMenuBotIcon>; caller orders by slot. */
    public function icons(): HasMany
    {
        return $this->hasMany(TfAttachMenuBotIcon::class, 'bot_id', 'bot_id');
    }

    /** 1:N — the Vector<AttachMenuPeerType>; caller orders by slot. */
    public function peerTypes(): HasMany
    {
        return $this->hasMany(TfAttachMenuBotPeerType::class, 'bot_id', 'bot_id');
    }

    /**
     * The full composite key of this row, for writing a child fact.
     *
     * @return array<string, int>
     */
    public function childKey(): array
    {
        return [
            'account_id' => (int) $this->account_id,
            'bot_id' => (int) $this->bot_id,
        ];
    }
}
