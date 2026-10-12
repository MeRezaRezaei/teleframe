<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Identity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Q8 ruling: package-owned binding between a Laravel User (nullable morph —
 * works with NO Laravel User, plain-PHP apps) and the STABLE telegram bigint
 * identity (tl_user_id), tenant-scoped by account_id.
 *
 * - Keyed on the stable telegram id, never the anchor UUID (entity anchors
 *   are re-created across constructor transitions; the binding must not move).
 * - Binding OUTLIVES instance deletion: `contact_lost` flag (default false)
 *   instead of cascade delete. The morph columns carry NO foreign key by
 *   design — deleting a Laravel User never cascades into bindings, and
 *   dropping a telegram entity never deletes the binding.
 * - unique (tl_user_id, account_id): one binding per telegram user per tenant.
 *   account_id nullable => rows that exist in a plain-PHP (no-tenant) host.
 *
 * findTF-tenancy (Q7): every row lookup defaults to the PRIMARY account and
 * only ever honors an explicit accountId override — silent cross-tenant
 * global lookups by tg id are the tenancy contract's banned shape, so this
 * class never performs them.
 *
 * @property int|null $user_id Bound Laravel User id, or null in plain-PHP mode.
 * @property string|null $user_type Bound Laravel User class, or null in plain-PHP mode.
 * @property int $tl_user_id Stable telegram bigint identity.
 * @property int|null $account_id Tenant account id, or null for no-tenant hosts.
 * @property bool $contact_lost Upstream deleted/blocked flag (row survives).
 * @property string|null $passport_fields Verified SecureValueType NAMES, comma-joined — never their values.
 * @property Carbon|null $passport_verified_at When a Telegram Passport login last completed.
 * @property Model|null $user Loaded morph target.
 */
final class TlUserBinding extends Model
{
    protected $table = 'tl_user_bindings';

    /**
     * The columns a caller may set.
     *
     * `passport_fields` and `passport_verified_at` are DELIBERATELY ABSENT.
     * They are provenance — the record that a verification happened, written
     * only by the server after a completed chain — and they were listed here,
     * which made a host's `TlUserBinding::create($request->all())` enough to
     * assert its own verification. Nothing in this package mass-assigns a
     * binding, so the hole was latent rather than live, but latent holes in
     * `$fillable` are the kind a host activates with ordinary admin CRUD and
     * never notices. Provenance is written through the named method below, not
     * through a fill.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_type',
        'user_id',
        'tl_user_id',
        'account_id',
        'contact_lost',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'user_id' => 'int',
        'tl_user_id' => 'int',
        'account_id' => 'int',
        'contact_lost' => 'bool',
        'passport_verified_at' => 'datetime',
    ];

    /**
     * Nullable morph back to the bound Laravel User (null in plain-PHP mode
     * or before the app ever binds a User — see Q8).
     */
    public function user(): MorphTo
    {
        return $this->morphTo('user', 'user_type', 'user_id');
    }

    /**
     * Resolve the bound Laravel User through the morph. Null when the row is
     * a plain no-Laravel-User binding (contact_lost tracking shape).
     */
    public function resolver(): ?Model
    {
        $user = $this->user;

        return $user instanceof Model ? $user : null;
    }

    /**
     * Mark the telegram account as lost (deleted/blocked upstream). The row
     * SURVIVES — the binding outlives instance deletion (Q8).
     */
    public function markContactLost(bool $lost = true): self
    {
        $this->forceFill(['contact_lost' => $lost])->save();

        return $this;
    }

    /**
     * Authentication provenance: stamp WHEN a Telegram Passport login last
     * completed on this binding and WHICH SecureValueType NAMES were verified.
     * A null `passport_verified_at` is the "never authenticated via Telegram"
     * answer, which is what makes a binding auditable without a second table.
     *
     * TYPES ONLY, enforced HERE rather than trusted from the caller: this is
     * the one place the string reaches a column, so it is where an accidental
     * value cannot survive. Only the three known SecureValueType names are
     * accepted (the same three `PassportPayload`/`PassportValueValidator`
     * speak), joined with a comma. Anything else — a decrypted address, a
     * phone number, an injected key — is DROPPED, so a caller that wrongly
     * passes values records a shorter list, never personal data. Nothing here
     * is ever logged.
     *
     * @param  list<string>  $fieldsVerified  SecureValueType names; anything
     *                                        not recognised is discarded
     */
    public function recordPassportProvenance(array $fieldsVerified = []): self
    {
        $types = [];
        foreach (['personal_details', 'email', 'phone'] as $type) {
            if (in_array($type, $fieldsVerified, true)) {
                $types[] = $type;
            }
        }

        $this->forceFill([
            'passport_fields' => $types === [] ? null : implode(',', $types),
            'passport_verified_at' => now(),
        ])->save();

        return $this;
    }

    /**
     * The verified SecureValueType NAMES of the last passport login as a list
     * (never their values). Empty when the binding has no passport history.
     *
     * @return list<string>
     */
    public function passportFieldsVerified(): array
    {
        $stored = $this->getAttribute('passport_fields');
        if (! is_string($stored) || $stored === '') {
            return [];
        }

        $types = [];
        foreach (explode(',', $stored) as $type) {
            $type = trim($type);
            if ($type !== '') {
                $types[] = $type;
            }
        }

        return $types;
    }

    /**
     * Q7 find helper: binding for (telegram id, tenant). accountId omitted =>
     * PRIMARY account default (config `teleframe.primary_account_id`,
     * env `TELEFRAME_PRIMARY_ACCOUNT_ID`, else the FIRST known account for
     * that tg id = min account_id), explicit accountId => exact tenant only.
     *
     * Never a bare cross-tenant global lookup: when no account can be
     * resolved the method returns null instead of scanning other tenants.
     */
    public static function bindingFor(int $tgId, ?int $accountId = null): ?self
    {
        $accountId ??= self::resolveAccountId($tgId);

        if ($accountId === null) {
            return self::query()->where('tl_user_id', $tgId)->where('account_id', null)->first();
        }

        return self::query()
            ->where('tl_user_id', $tgId)
            ->where('account_id', $accountId)
            ->first();
    }

    /**
     * Q7 primary-account default resolution: configured primary account id,
     * else the deterministic FIRST account that hosted this telegram id.
     */
    public static function resolveAccountId(int $tgId): ?int
    {
        $configured = IdentityConfig::primaryAccountId();
        if ($configured !== null) {
            return $configured;
        }

        $first = self::query()
            ->where('tl_user_id', $tgId)
            ->where('account_id', '!=', null)
            ->orderBy('account_id')
            ->value('account_id');

        return $first === null ? null : (int) $first;
    }

    /**
     * Upsert a binding row for (tl_user_id, account_id) and return it —
     * used by every write-hook so re-ingestion never duplicates (the unique
     * constraint is the backstop for concurrent writers).
     */
    public static function ensureBinding(int $tgId, ?int $accountId = null, ?Model $user = null): self
    {
        $binding = self::bindingFor($tgId, $accountId);

        if ($binding === null) {
            return self::query()->create([
                'user_type' => $user !== null ? $user::class : null,
                'user_id' => $user?->getKey(),
                'tl_user_id' => $tgId,
                'account_id' => $accountId,
                'contact_lost' => false,
            ]);
        }

        if ($user !== null && ($binding->user_type !== $user::class || (int) $binding->user_id !== (int) $user->getKey())) {
            $binding->forceFill([
                'user_type' => $user::class,
                'user_id' => (int) $user->getKey(),
            ])->save();
        }

        return $binding;
    }
}
