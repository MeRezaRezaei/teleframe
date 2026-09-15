<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Builder;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;

/**
 * tf_dialogs_draft — 1:1 object child of TfDialog (dialog.draft,
 * flags.2?DraftMessage). DraftMessage union (draftMessageEmpty /
 * draftMessage), constructor-discriminated. Nested reply_to / entities /
 * media / suggested_post / rich_message objects are deferred to the flat
 * write path.
 *
 * @property int $account_id
 * @property int $peer_type
 * @property int $peer_id
 * @property string $constructor
 * @property bool $no_webpage
 * @property bool $invert_media
 * @property string $message
 * @property int $date
 * @property int $effect
 */
final class TfDialogsDraft extends MirrorChildModel
{
    use AccountScoped;

    protected $table = 'tf_dialogs_draft';

    protected $primaryKey = 'account_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'peer_type' => 'integer',
        'peer_id' => 'integer',
        'no_webpage' => 'boolean',
        'invert_media' => 'boolean',
        'date' => 'integer',
        'effect' => 'integer',
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
