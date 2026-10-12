<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Builder;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Hand-authored NF5 mirror of the updates.Difference union — table
 * tf_update_differences. Vectors (new_messages, new_encrypted_messages,
 * other_updates, chats, users) are mirrored by their own domain tables; this
 * row records the sync state only.
 *
 * @property int $account_id
 * @property int $position
 * @property string $constructor
 */
final class TfUpdateDifference extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_update_differences';

    protected $primaryKey = 'account_id';

    /** @var list<string> */
    protected $fillable = ['account_id', 'position', 'constructor'];

    /** @var array<string, string> */
    protected $casts = [
        'account_id' => 'int',
        'position' => 'int',
    ];

    /**
     * @return array<string, int>
     */
    public function childKey(): array
    {
        return [
            'account_id' => (int) $this->account_id,
            'position' => (int) $this->position,
        ];
    }

    /**
     * @param  class-string<MirrorChildModel>  $child
     */
    private function child(string $child): Builder
    {
        return $child::query()
            ->where('account_id', (int) $this->account_id)
            ->where('position', (int) $this->position);
    }

    public function date(): Builder
    {
        return $this->child(TfUpdateDifferenceDate::class);
    }

    public function seq(): Builder
    {
        return $this->child(TfUpdateDifferenceSeq::class);
    }

    public function pts(): Builder
    {
        return $this->child(TfUpdateDifferencePts::class);
    }

    /** Embedded updates.State snapshot (state / intermediate_state). */
    public function state(): Builder
    {
        return $this->child(TfUpdateDifferenceState::class);
    }
}
