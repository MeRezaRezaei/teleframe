<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Builder;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Hand-authored NF5 mirror of the updates/dial routing receipt — table
 * tf_update_routing, one row per delivered container (updates, updatesCombined,
 * updatesTooLong, updateShort, updateShortMessage, updateShortChatMessage,
 * updateShortSentMessage). msg_id is the transport-envelope message id
 * (0 for non-pushed receipts) and is the quick-ack registry key.
 *
 * @property int $account_id
 * @property int $seq
 * @property int $position
 * @property string $constructor
 * @property int $msg_id
 */
final class TfUpdateRouting extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_update_routing';

    protected $primaryKey = 'account_id';

    /** @var list<string> */
    protected $fillable = ['account_id', 'seq', 'position', 'constructor', 'msg_id'];

    /** @var array<string, string> */
    protected $casts = [
        'account_id' => 'int',
        'seq' => 'int',
        'position' => 'int',
        'msg_id' => 'int',
    ];

    /**
     * @return array<string, int>
     */
    public function childKey(): array
    {
        return [
            'account_id' => (int) $this->account_id,
            'seq' => (int) $this->seq,
            'position' => (int) $this->position,
        ];
    }

    public function date(): Builder
    {
        return TfUpdateRoutingDate::query()
            ->where('account_id', (int) $this->account_id)
            ->where('seq', (int) $this->seq)
            ->where('position', (int) $this->position);
    }

    /** updatesCombined only. */
    public function seqStart(): Builder
    {
        return TfUpdateRoutingSeqStart::query()
            ->where('account_id', (int) $this->account_id)
            ->where('seq', (int) $this->seq)
            ->where('position', (int) $this->position);
    }

    public function ack(): Builder
    {
        return TfUpdateRoutingAck::query()
            ->where('account_id', (int) $this->account_id)
            ->where('seq', (int) $this->seq)
            ->where('position', (int) $this->position);
    }
}
