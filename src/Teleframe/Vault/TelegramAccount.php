<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Vault;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Credential vault: one row per logged-in Telegram account.
 *
 * Two kinds (single table, `type` column):
 * - `user`: MTProto — requires a non-null app_id FK (api_id/hash source)
 *   plus an encrypted `session` (SessionData export string).
 * - `bot`: Bot API over HTTP needs only an encrypted `bot_token`
 *   (app_id nullable); Bot over MTProto additionally links app_id and
 *   carries an encrypted `session` (via auth.importBotAuthorization).
 *
 * `user_id`: the Telegram account id (bigint, unique across the
 * platform). Used by Vault::defaultAccount() to resolve the env
 * TELEFRAME_DEFAULT_ACCOUNT_ID lookup (the env holds this id, not
 * a label). Extracted from the session string at finalizeLogin time,
 * or entered manually via --telegram-id.
 *
 * `revoked_at`: WHEN the server rejected this account's auth key
 * (null = never observed revoked). Set once by markRevoked() and
 * cleared by a fresh login; it is what lets a host tell WHICH
 * accounts need re-login, and what makes
 * TeleframeClient::resolveVaultAccount() refuse a dead account by
 * name instead of dialing it forever.
 *
 * Owner morph nullable (TlUserBinding pattern — no FK, rows outlive User
 * deletion). Deleting an account never touches its app (separate
 * integrity); deleting an app nulls app_id (nullOnDelete).
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $app_id
 * @property string $label
 * @property string $type
 * @property string|null $owner_type
 * @property int|null $owner_id
 * @property string|null $session
 * @property string|null $bot_token
 * @property int $dc_id
 * @property Carbon|null $revoked_at
 * @property TelegramApp|null $app
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class TelegramAccount extends Model
{
    protected $table = 'telegram_accounts';

    public const TYPE_USER = 'user';

    public const TYPE_BOT = 'bot';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'app_id',
        'label',
        'type',
        'owner_type',
        'owner_id',
        'session',
        'bot_token',
        'dc_id',
    ];

    /**
     * Credential columns — never log, dump, or commit their raw values.
     *
     * `session` holds a SessionData::exportString() (by-design credential:
     * it is the full MTProto auth-key blob, src/Core/MTProto/SessionData.php).
     * `bot_token` is equivalently sensitive. Both are encrypted at rest via
     * the `encrypted` casts below. Guard: exportString output must never be
     * written to logs, exception messages, tests, or sessions-bound metrics.
     *
     * They are also $hidden: every serialization path this package ships uses
     * an explicit allow-list, but an `encrypted` cast DECRYPTS on attribute
     * read, so one careless `json($account)` / `toArray()` in a host app would
     * hand out plaintext credentials. $hidden costs nothing and makes the
     * accidental path a no-op instead of a disclosure.
     *
     * @var list<string>
     */
    protected $hidden = [
        'session',
        'bot_token',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'user_id' => 'int',
        'app_id' => 'int',
        'owner_id' => 'int',
        'dc_id' => 'int',
        'session' => 'encrypted',
        'bot_token' => 'encrypted',
        'revoked_at' => 'datetime',
    ];

    /**
     * Has the server rejected this account's auth key?
     *
     * True is permanent for the CURRENT session: a key the server has deleted
     * cannot be revived, only replaced by a fresh login. The credential columns
     * are deliberately left intact — refusing to serve a revoked account is a
     * read-path decision, not an irreversible write.
     */
    public function isRevoked(): bool
    {
        return $this->getAttribute('revoked_at') !== null;
    }

    /**
     * Record the revocation. Idempotent by design: the FIRST call stamps
     * `revoked_at` and returns true, every later call is a no-op returning
     * false. That is what makes "notify a host exactly once" implementable —
     * a poller that keeps seeing the same dead key can retry safely, and the
     * host still hears about it once.
     *
     * @return bool whether THIS call is the one that recorded the revocation
     */
    public function markRevoked(): bool
    {
        if ($this->isRevoked()) {
            return false;
        }

        $this->setAttribute('revoked_at', $this->freshTimestamp());
        $this->save();

        return true;
    }

    /**
     * Stamp the account whose `user_id` is $telegramUserId, if any.
     *
     * The poller knows the TELEGRAM id (that is the only identity a session
     * carries), not the vault row id, so this is the lookup the revocation
     * path needs. Returns the account only when THIS call recorded the
     * revocation — the signal to tell the host, not to report "already known".
     */
    public static function markRevokedFor(int $telegramUserId): ?self
    {
        $account = self::query()->where('user_id', $telegramUserId)->first();

        if (! $account instanceof self || ! $account->markRevoked()) {
            return null;
        }

        return $account;
    }

    /**
     * Nullable morph back to the owning Laravel User.
     */
    public function owner(): MorphTo
    {
        return $this->morphTo('owner', 'owner_type', 'owner_id');
    }

    /**
     * @return BelongsTo<TelegramApp, $this>
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(TelegramApp::class, 'app_id');
    }

    /**
     * Resolved credential bundle for TeleframeClient wrappers.
     *
     * @return array{api_id: int|null, api_hash: string|null, session: string|null, bot_token: string|null, dc_id: int, type: string}
     */
    public function credentials(): array
    {
        $app = $this->getRelationValue('app') ?? $this->app()->first();

        return [
            'api_id' => $app instanceof TelegramApp ? (int) $app->getAttribute('api_id') : null,
            'api_hash' => $app instanceof TelegramApp ? (string) $app->getAttribute('api_hash') : null,
            'session' => $this->getAttribute('session'),
            'bot_token' => $this->getAttribute('bot_token'),
            'dc_id' => (int) $this->getAttribute('dc_id'),
            'type' => (string) $this->getAttribute('type'),
        ];
    }

    protected static function booted(): void
    {
        self::saving(static function (self $account): void {
            if ($account->type !== self::TYPE_USER && $account->type !== self::TYPE_BOT) {
                throw new InvalidArgumentException("telegram_accounts.type must be 'user' or 'bot'.");
            }

            if ($account->type === self::TYPE_USER && $account->app_id === null) {
                throw new InvalidArgumentException("a 'user' account requires app_id (api_id/hash source).");
            }

            if ($account->type === self::TYPE_BOT && $account->bot_token === null && $account->session === null) {
                throw new InvalidArgumentException("a 'bot' account requires bot_token or session.");
            }
        });
    }
}
