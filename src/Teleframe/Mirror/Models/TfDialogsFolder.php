<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Builder;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;

/**
 * tf_dialogs_folder — 1:1 object child of TfDialog (dialogFolder.folder,
 * Folder). Single-ctor Folder, constructor-discriminated; the nested photo
 * (ChatPhoto) object is deferred to the flat write path.
 *
 * @property int $account_id
 * @property int $peer_type
 * @property int $peer_id
 * @property string $constructor
 * @property bool $autofill_new_broadcasts
 * @property bool $autofill_public_groups
 * @property bool $autofill_new_correspondents
 * @property int $id
 * @property string $title
 */
final class TfDialogsFolder extends MirrorChildModel
{
    use AccountScoped;

    protected $table = 'tf_dialogs_folder';

    protected $primaryKey = 'account_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'peer_type' => 'integer',
        'peer_id' => 'integer',
        'autofill_new_broadcasts' => 'boolean',
        'autofill_public_groups' => 'boolean',
        'autofill_new_correspondents' => 'boolean',
        'id' => 'integer',
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
