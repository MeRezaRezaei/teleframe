<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\AccountLifecycle\AccountLifecycleObserver;

/**
 * DB-backed runtime account registry (Cycle-4 RFC, Option A): the live
 * "which account/bot does the daemon drive" answer. One row per Telegram
 * account_id; the legacy config('teleframe.daemon.accounts') stays the
 * bootstrap seed, this table becomes the registry the daemon re-reads on a
 * reload signal (StreamSchema::RELOAD_CHANNEL, kind 'accounts').
 *
 * session_ref is deliberately OPAQUE — a reference into the vault/host
 * session resolver, NEVER a session string. No code path here ever
 * reads, prints, or resolves it into a credential.
 *
 * The lifecycle observer (AccountLifecycleObserver) is registered through
 * the model's own booted() hook — Cycle-4 wiring is model-owned, the
 * provider does NOT observe (documented in the Cycle-4 RFC). A host that
 * never loads this model pays nothing; one that does gets hot
 * account/bot add-remove signalling with zero config. The observer
 * resolves its bus redis best-effort, so a host without a working bus
 * redis still gets the AccountAdded/AccountRemoved events (the reload
 * publish is skipped, never fatal).
 *
 * @property int $id
 * @property string $label
 * @property int $dc_id
 * @property int|null $port
 * @property string $kind
 * @property string $session_ref
 * @property bool $enabled
 */
final class TfAccount extends Model
{
    protected $table = 'tf_accounts';

    public const KIND_ACCOUNT = 'account';

    public const KIND_BOT = 'bot';

    /** @var bool telegram account_ids come from Telegram, not auto-increment */
    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $keyType = 'int';

    /** @var list<string> */
    protected $fillable = [
        'id',
        'label',
        'dc_id',
        'port',
        'kind',
        'session_ref',
        'enabled',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'dc_id' => 'int',
        'port' => 'int',
        'enabled' => 'bool',
    ];

    public function isBot(): bool
    {
        return $this->kind === self::KIND_BOT;
    }

    /**
     * Model-owned observer wiring (Cycle-4 RFC): see the class docblock.
     * A string registration lets the Event Dispatcher resolve the observer
     * from the CURRENT container at fire time, so a host rebinding
     * RedisConnectionContract picks the change up without re-boot.
     */
    protected static function booted(): void
    {
        self::observe(AccountLifecycleObserver::class);
    }
}
