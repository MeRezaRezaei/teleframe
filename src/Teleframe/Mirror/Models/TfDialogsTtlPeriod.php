<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Builder;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;

/**
 * tf_dialogs_ttl_period — 1:1 fact of TfDialog (dialog.ttl_period,
 * flags.4?int).
 *
 * @property int $account_id
 * @property int $peer_type
 * @property int $peer_id
 * @property int $ttl_period
 */
final class TfDialogsTtlPeriod extends MirrorChildModel
{
    use AccountScoped;

    protected $table = 'tf_dialogs_ttl_period';

    protected $primaryKey = 'account_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'peer_type' => 'integer',
        'peer_id' => 'integer',
        'ttl_period' => 'integer',
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
