<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Hand-authored NF5 mirror of updates.State (updates#a56c2a3e) — table
 * tf_update_state, one row per account. This is the updates.getState /
 * updates.getDifference sync seam: the writer upserts the server snapshot
 * here before consuming a difference set.
 *
 * @property int $account_id
 * @property int $pts
 * @property int $qts
 * @property int $date
 * @property int $seq
 * @property int $unread_count
 */
final class TfUpdateState extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_update_state';

    protected $primaryKey = 'account_id';

    /** @var list<string> */
    protected $fillable = ['account_id', 'pts', 'qts', 'date', 'seq', 'unread_count'];

    /** @var array<string, string> */
    protected $casts = [
        'account_id' => 'int',
        'pts' => 'int',
        'qts' => 'int',
        'date' => 'int',
        'seq' => 'int',
        'unread_count' => 'int',
    ];

    private const POINTS_FIELDS = ['pts', 'qts', 'date', 'seq', 'unread_count'];

    /**
     * Upsert the updates.State snapshot for one account. Only the fields
     * present in $state are written (merge semantics): a full getState /
     * getDifference response carries all five, partial updates keep the
     * prior values.
     *
     * @param  array<string, int>  $state
     */
    public static function syncSnapshot(int $accountId, array $state): self
    {
        $existing = self::forAccount($accountId)->first();
        $row = $existing instanceof self ? $existing : new self;
        $row->account_id = $accountId;
        foreach (self::POINTS_FIELDS as $field) {
            if (array_key_exists($field, $state)) {
                $row->setAttribute($field, (int) $state[$field]);
            }
        }
        $row->save();

        return $row;
    }
}
