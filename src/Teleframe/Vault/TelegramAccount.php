<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Vault;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
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
 * @property TelegramApp|null $app
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

    /** @var array<string, string> */
    protected $casts = [
        'user_id' => 'int',
        'app_id' => 'int',
        'owner_id' => 'int',
        'dc_id' => 'int',
        'session' => 'encrypted',
        'bot_token' => 'encrypted',
    ];

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
        static::saving(static function (self $account): void {
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
