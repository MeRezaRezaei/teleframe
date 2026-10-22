<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Services;

/**
 * Per-CONNECTION read-only intent for the relay vault terminal, recovered
 * from the one-shot relay ticket the socket redeemed.
 *
 * Why this exists (finding C5): ReadOnlyScope was armed ONLY by the HTTP
 * route middleware ScopeTelegramReadOnly, which never runs inside the
 * long-lived `teleframe:relay` console daemon. In the process that actually
 * enforces anything, isReadOnly() was permanently false and every RPC reached
 * the managed account. A per-request HTTP frame is both the wrong LIFETIME and
 * the wrong SOURCE for a socket.
 *
 * This registry is the SOURCE: the ticket a connection redeemed carries an
 * explicit read-only grant, recorded here at redeem time (RelayCommand's
 * ticket validator) and consulted per dispatch by ReadOnlyVaultDispatch for
 * the identity the answerer threaded into the dispatch. The identity
 * {account_id, user_id} is what survives VaultMtprotoAnswerer's grant
 * normalization, so it names the connection deterministically — two sockets
 * holding different tickets never share an entry.
 *
 * DENY BY DEFAULT: a grant that cannot be determined (no entry, an entry older
 * than the retention window, a malformed identity) answers read-only. An
 * entry is also MONOTONIC toward the restrictive: once an identity is
 * read-only, a later grant for the same identity cannot loosen it for the
 * retention window, so a freshly minted read-write ticket can never widen a
 * socket that is already established under a read-only grant. This is why
 * fail-closed holds even in a race.
 *
 * Disarming: entries carry a recorded-at stamp and are dropped once they age
 * past the retention window (default 3600s, far longer than the ticket TTL), so
 * a closed connection leaves nothing behind. Expiry can only ever move an
 * identity from "read-write" to "undetermined ⇒ read-only", never the reverse.
 * forget() disarms an identity explicitly.
 *
 * Plain PHP, no facades, no credentials: only the granted account id, the
 * granted Laravel user id, and a boolean are stored. Zero regex (string
 * functions only).
 */
final class RelayReadOnlyGrants
{
    /** Default retention window for a recorded grant, in seconds. */
    public const DEFAULT_RETENTION_SECONDS = 3600;

    /**
     * @var array<string, array{read_only: bool, recorded_at: float}>
     */
    private array $entries = [];

    /**
     * @param  bool  $undeterminedIsReadOnly  what an unknown / expired / malformed grant answers:
     *                                        true (default, safe) = read-only; false = fail open
     *                                        and the terminal loses its guard for that RPC
     * @param  int|null  $retentionSeconds  how long a recorded grant is honoured; null = the default
     */
    public function __construct(
        private readonly bool $undeterminedIsReadOnly = true,
        private readonly ?int $retentionSeconds = null,
    ) {}

    /**
     * Record the read-only intent a redeemed ticket carried, keyed by the
     * granted identity. Monotonic: a read-write grant never overwrites an
     * existing read-only one.
     *
     * @param  int  $accountId  the account the ticket granted
     * @param  int|string|null  $userId  the Laravel user captured at mint time
     * @param  bool  $readOnly  the ticket's explicit read-only intent
     */
    public function remember(int $accountId, int|string|null $userId, bool $readOnly): void
    {
        $key = $this->key($accountId, $userId);
        if ($key === null) {
            return;
        }

        $existing = $this->entry($key);
        if ($existing !== null && $existing['read_only']) {
            return;
        }

        $this->entries[$key] = ['read_only' => $readOnly, 'recorded_at' => microtime(true)];
    }

    /**
     * Whether the connection serving `$grant` must be refused every
     * state-changing RPC. Fails closed per the constructor flag.
     *
     * @param  array{account_id?: mixed, user_id?: mixed}|null  $grant  the per-connection ticket grant
     */
    public function isReadOnly(?array $grant): bool
    {
        $accountId = $this->accountIdOf($grant);
        if ($accountId === null) {
            return $this->undeterminedIsReadOnly;
        }

        $key = $this->key($accountId, $this->userIdOf($grant));
        if ($key === null) {
            return $this->undeterminedIsReadOnly;
        }

        $entry = $this->entry($key);
        if ($entry === null) {
            return $this->undeterminedIsReadOnly;
        }

        return $entry['read_only'];
    }

    /**
     * Disarm one identity explicitly (a closed connection, a revoked grant).
     * After this the identity is UNDETERMINED — which, by default, still means
     * read-only.
     */
    public function forget(int $accountId, int|string|null $userId): void
    {
        $key = $this->key($accountId, $userId);
        if ($key !== null) {
            unset($this->entries[$key]);
        }
    }

    /** Drop every recorded grant. */
    public function purge(): void
    {
        $this->entries = [];
    }

    /** How many grants are currently recorded (diagnostics). */
    public function count(): int
    {
        return count($this->entries);
    }

    /**
     * The live entry for a key, dropping it when it aged out of the retention
     * window (the disarm path for a connection that ended).
     *
     * @return array{read_only: bool, recorded_at: float}|null
     */
    private function entry(string $key): ?array
    {
        $entry = $this->entries[$key] ?? null;
        if ($entry === null) {
            return null;
        }

        $retention = $this->retentionSeconds ?? self::DEFAULT_RETENTION_SECONDS;
        if ($retention > 0 && microtime(true) - $entry['recorded_at'] > $retention) {
            unset($this->entries[$key]);

            return null;
        }

        return $entry;
    }

    /**
     * @param  array<string, mixed>|null  $grant
     */
    private function accountIdOf(?array $grant): ?int
    {
        $accountId = $grant['account_id'] ?? null;

        return is_int($accountId) && $accountId >= 1 ? $accountId : null;
    }

    /**
     * @param  array<string, mixed>|null  $grant
     */
    private function userIdOf(?array $grant): int|string|null
    {
        $userId = $grant['user_id'] ?? null;
        if (is_int($userId)) {
            return $userId;
        }
        if (is_string($userId) && $userId !== '') {
            return $userId;
        }

        return null;
    }

    /**
     * A collision-free key for a granted identity. The Laravel user is
     * type-tagged so the int 5 and the string "5" are distinct identities.
     */
    private function key(int $accountId, int|string|null $userId): ?string
    {
        if ($accountId < 1) {
            return null;
        }

        $taggedUser = match (true) {
            $userId === null => 'none',
            is_int($userId) => 'i'.$userId,
            default => 's'.$userId,
        };

        return $accountId.'|'.$taggedUser;
    }
}
