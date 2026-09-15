<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * NF5 mirror of the botApp union (botAppNotModified | botApp) — table
 * tf_bot_apps, constructor-discriminated. Scalars type-default so the empty
 * botAppNotModified row places.
 *
 * @property int $account_id
 * @property string $constructor
 * @property int $id
 */
final class TfBotApp extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_bot_apps';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'int',
        'id' => 'int',
        'access_hash' => 'int',
        'hash' => 'int',
    ];

    public function photo(): HasOne
    {
        return $this->hasOne(TfBotAppPhoto::class, 'id', 'id');
    }

    public function document(): HasOne
    {
        return $this->hasOne(TfBotAppDocument::class, 'id', 'id');
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
            'id' => (int) $this->id,
        ];
    }
}
