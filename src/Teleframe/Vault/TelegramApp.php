<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Vault;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Credential vault: one row per my.telegram.org application.
 *
 * Owner morph nullable (same pattern as TlUserBinding — works with NO
 * Laravel User, plain-PHP apps; the morph carries NO foreign key so
 * deleting a User never cascades into apps). `api_hash` uses the
 * `encrypted` cast (ciphertext at rest via the Laravel encrypter).
 *
 * @property int $id
 * @property string $label
 * @property string|null $owner_type
 * @property int|null $owner_id
 * @property int $api_id
 * @property string $api_hash
 */
final class TelegramApp extends Model
{
    protected $table = 'telegram_apps';

    /** @var list<string> */
    protected $fillable = [
        'label',
        'owner_type',
        'owner_id',
        'api_id',
        'api_hash',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'api_id' => 'int',
        'owner_id' => 'int',
        'api_hash' => 'encrypted',
    ];

    /**
     * Nullable morph back to the owning Laravel User (null in plain-PHP
     * mode or for package-level shared apps like `teleframe`).
     */
    public function owner(): MorphTo
    {
        return $this->morphTo('owner', 'owner_type', 'owner_id');
    }

    /**
     * Accounts linked to this app. Deleting the app nulls their app_id
     * (nullOnDelete) — accounts survive, MTProto unusable until re-linked.
     *
     * @return HasMany<TelegramAccount, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(TelegramAccount::class, 'app_id');
    }
}
