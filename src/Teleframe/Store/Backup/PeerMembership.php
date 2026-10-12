<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Backup;

use MeRezaRezaei\Teleframe\Store\ResilientCall;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * PeerMembership (remainder design §2/§6, Cycle 11 wave 2) — the add-member
 * seam for channel/supergroup backup targets: invite redundant member
 * accounts through `channels.inviteToChannel` (already productized as
 * `UserAccountScope::inviteToChannel`, UserAccountScope.php:373-387), join
 * public peers via `UserAccountScope::joinChannel` (UserAccountScope.php:322),
 * and probe post rights best-effort via `UserAccountScope::getParticipants`
 * (UserAccountScope.php:396).
 *
 * The scope is INJECTED as a plain `object` — zero container calls, the same
 * guard-and-call idiom RelayTerminal uses for its bound client — so this
 * class is plain-PHP constructible (standalone-smoke gate) and offline
 * testable with an anonymous-class stub. Accounts are int user ids /
 * username strings; never session strings.
 *
 * ResilientCall posture (design §6: "wraps it in the store's ResilientCall
 * (flood/migrate) posture"): every transport call funnels through the
 * injected ResilientCall when one is given (method name + params, exactly
 * its `__invoke(string, array): array` surface), else the scope method is
 * called directly and its exceptions propagate. Any transport failure is
 * logged at error level and RETHROWN — logging never swallows (AGENTS.md).
 * The ONE deliberate exception is `hasPostRights()`: its probe is
 * best-effort by contract ("accurate-enough + never fatals", design §6
 * table) and falls back to the permissive default after logging.
 *
 * hasPostRights() ruling (documented, design §6 table): channel targets are
 * ASSUMED OWNED (created by the account → admin by construction) and return
 * true; group targets probe the scope's getParticipants (admins filter)
 * best-effort, and ANY probe failure (no participants-like call on the
 * scope, transport error, offline/unconfigured) logs at error and returns
 * the PERMISSIVE default true — group member posts are the default
 * permission, and the probe must never fatal the caller. Detecting a
 * NON-admin account on a foreign channel would require the account's own
 * user id from the session — out of scope for this session-free seam; such
 * targets are the caller's responsibility to verify (e.g. on prune).
 *
 * Missing scope methods are loud `BackupException`s, never silent no-ops
 * (design §2 BackupException row; §11 "loud, never silent").
 *
 * RULING (null scope — wave-2 reconcile): the scope ctor param is
 * `?object` and MAY be null. This seam is composed at the CALL SITE by
 * whoever holds a live scope (the package provider never binds it — a
 * UserAccountScope is a session-backed client and must not be materialized
 * at Laravel boot); a null scope therefore throws BackupException at
 * FIRST USE in ensureMember()/joinPublic() — never at construction, so
 * construction stays plain-PHP and any future null-safe container binding
 * can never fatal bind time. hasPostRights() keeps its NEVER-throw
 * contract even with a null scope: the probe is skipped and the
 * permissive default true is returned (logged when a logger is bound).
 *
 * PeerTarget is the wave-2 sibling file (design §2 row:
 * {bucket, peerId, accessHash, peerType channel|group, mode, passphrase?,
 * memberAccounts}, `inputPeer(): array`) — this seam consumes only
 * `inputPeer()` + `peerType`, nothing session-shaped.
 *
 * Provenance:
 * docs/superpowers/plans/2026-09-24-backup-store-remainder-design.md §2
 * (PeerMembership row), §6 (add-member / join / post-rights table) —
 * Cycle 11 wave 2.
 */
final class PeerMembership
{
    /**
     * @param  object|null  $scope  the live UserAccountScope (or offline
     *                              stub) exposing inviteToChannel /
     *                              joinChannel / getParticipants; null is
     *                              tolerated at CONSTRUCTION and throws
     *                              BackupException LOUDLY at first use in
     *                              ensureMember()/joinPublic() (see the
     *                              null-scope RULING in the class docblock)
     */
    public function __construct(
        private readonly ?object $scope = null,
        private readonly ?ResilientCall $call = null,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * Invite redundant member accounts (int user ids / username strings)
     * into a channel/supergroup backup target via channels.inviteToChannel,
     * wrapped in the ResilientCall posture when one is injected.
     *
     * @param  PeerTarget  $target  the backup destination (inputPeer() carries channel_id + access_hash)
     * @param  list<int|string>  $accounts  user ids or usernames — never session strings
     * @return array<string, mixed> raw channels.inviteToChannel result (Updates)
     *
     * @throws BackupException when the scope (fallback path) exposes no inviteToChannel method
     */
    public function ensureMember(PeerTarget $target, array $accounts): array
    {
        $peer = $target->inputPeer();

        if ($this->call !== null) {
            try {
                return ($this->call)('channels.inviteToChannel', [
                    'channel' => $peer,
                    'users' => $accounts,
                ]);
            } catch (Throwable $e) {
                $this->logError('backup membership: inviteToChannel failed (resilient call)', $e, $target);
                throw $e;
            }
        }

        // Null scope → BackupException LOUD here (first use), never at
        // construction (null-scope RULING in the class docblock).
        $scope = $this->scope();

        if (! method_exists($scope, 'inviteToChannel')) {
            $this->logError(
                sprintf('backup membership: scope %s exposes no inviteToChannel — cannot invite member accounts', $scope::class),
                null,
                $target,
            );
            throw new BackupException(sprintf(
                'backup membership: scope %s does not expose inviteToChannel — cannot invite member accounts',
                $scope::class,
            ));
        }

        try {
            return $scope->inviteToChannel($peer, $accounts);
        } catch (Throwable $e) {
            $this->logError('backup membership: inviteToChannel failed', $e, $target);
            throw $e;
        }
    }

    /**
     * Join a public channel/supergroup peer via channels.joinChannel,
     * wrapped in the ResilientCall posture when one is injected.
     * The scope's real signature is `joinChannel(int|string|array $channel): array`
     * (one argument) — the peer is passed positionally, not as a params map.
     *
     * @param  PeerTarget  $target  the backup destination (inputPeer() carries channel_id + access_hash)
     * @return array<string, mixed> raw channels.joinChannel result (Updates)
     *
     * @throws BackupException when the scope (fallback path) exposes no joinChannel method
     */
    public function joinPublic(PeerTarget $target): array
    {
        $peer = $target->inputPeer();

        if ($this->call !== null) {
            try {
                return ($this->call)('channels.joinChannel', ['channel' => $peer]);
            } catch (Throwable $e) {
                $this->logError('backup membership: joinChannel failed (resilient call)', $e, $target);
                throw $e;
            }
        }

        // Null scope → BackupException LOUD here (first use), never at
        // construction (null-scope RULING in the class docblock).
        $scope = $this->scope();

        if (! method_exists($scope, 'joinChannel')) {
            $this->logError(
                sprintf('backup membership: scope %s exposes no joinChannel — cannot join public peer', $scope::class),
                null,
                $target,
            );
            throw new BackupException(sprintf(
                'backup membership: scope %s does not expose joinChannel — cannot join public peer',
                $scope::class,
            ));
        }

        try {
            return $scope->joinChannel($peer);
        } catch (Throwable $e) {
            $this->logError('backup membership: joinChannel failed', $e, $target);
            throw $e;
        }
    }

    /**
     * Best-effort post-rights probe (design §6 table). NEVER throws.
     *
     * Ruling, documented: channel targets are assumed OWNED (created by the
     * account → admin by construction) → true. Group targets probe the
     * scope's getParticipants with the admins filter; if the scope exposes
     * no participants-like call, the probe errors, or the peer is
     * unreachable offline, the PERMISSIVE default true is returned and the
     * failure is logged — group member posts are the default permission and
     * this seam must not fatal the backup loop.
     */
    public function hasPostRights(PeerTarget $target): bool
    {
        if ($target->peerType === 'channel') {
            return true;
        }

        // Null scope keeps the NEVER-throw contract: the probe is
        // skipped and the permissive default true is returned (the
        // null-scope RULING in the class docblock).
        if ($this->scope === null) {
            $this->logError(
                'backup membership: no scope bound — post-rights probe skipped, permissive true',
                null,
                $target,
            );

            return true;
        }

        $scope = $this->scope;

        if (! method_exists($scope, 'getParticipants')) {
            $this->logError(
                sprintf('backup membership: scope %s exposes no getParticipants — post-rights probe skipped, permissive true', $scope::class),
                null,
                $target,
            );

            return true;
        }

        try {
            $scope->getParticipants($target->inputPeer(), ['_' => 'channelParticipantsAdmins'], 0, 50);

            return true;
        } catch (Throwable $e) {
            $this->logError('backup membership: post-rights probe failed — permissive true, verify access before pruning', $e, $target);

            return true;
        }
    }

    /**
     * STRICT, three-state post-rights probe — the one this module's
     * UPLOAD path uses, as distinct from the permissive hasPostRights()
     * above. It exists because "probe failed, assume yes" is correct for a
     * best-effort guard (prune) and WRONG for a gate that must refuse a
     * peer the account cannot post in BEFORE the first upload: a refusal
     * that never fires is indistinguishable from a bug.
     *
     * Ruling (documented, the same shape as hasPostRights plus one
     * unambiguous signal):
     *
     *  - channel targets are ASSUMED OWNED → true (the hasPostRights ruling
     *    is unchanged and still stands: created by the account → admin by
     *    construction, and detecting a non-admin account would need the
     *    account's own user id from the session, out of scope here).
     *  - group targets probe getParticipants with the admins filter. The
     *    reply's participant list is the only UNAMBIGUOUS verdict available
     *    without the account's own id: an EMPTY admin set is a definitive
     *    false (there is no identity the account could post as an admin),
     *    a NON-EMPTY one is true.
     *  - no scope bound, no participants-like call, a transport error, or a
     *    reply carrying neither a count nor a participant list → null
     *    (UNVERIFIABLE). null is NOT a denial: the caller decides, and the
     *    upload path documents what it does with it.
     *
     * NEVER throws, exactly like hasPostRights().
     */
    public function probePostRights(PeerTarget $target): ?bool
    {
        if ($target->peerType === 'channel') {
            return true;
        }

        if ($this->scope === null || ! method_exists($this->scope, 'getParticipants')) {
            return null;
        }

        try {
            $result = $this->scope->getParticipants($target->inputPeer(), ['_' => 'channelParticipantsAdmins'], 0, 50);
        } catch (Throwable $e) {
            $this->logError('backup membership: strict post-rights probe failed — unverifiable', $e, $target);

            return null;
        }

        return self::adminVerdict(is_array($result) ? $result : []);
    }

    /**
     * The three-state verdict from a getParticipants reply: false for an
     * empty admin set, true for a non-empty one, null when the reply says
     * nothing about participants at all. Zero regex — a count/participants
     * lookup, nothing more.
     *
     * @param  array<string, mixed>  $result
     */
    private static function adminVerdict(array $result): ?bool
    {
        $participants = $result['participants'] ?? null;
        if (is_array($participants)) {
            return $participants !== [];
        }

        $count = $result['count'] ?? null;
        if (is_int($count) || (is_string($count) && ctype_digit($count))) {
            return (int) $count > 0;
        }

        return null;
    }

    /**
     * The live scope, or a loud BackupException at FIRST USE — the
     * null-scope RULING: construction stays plain-PHP (and null-safe for
     * any future container binding); use is where a missing scope is
     * discovered.
     */
    private function scope(): object
    {
        if ($this->scope === null) {
            throw new BackupException(
                'backup membership: no scope bound — a live UserAccountScope (or a stub exposing inviteToChannel/joinChannel/getParticipants) is composed at the call site, never resolved at construction.',
            );
        }

        return $this->scope;
    }

    /**
     * PSR-3 error log (no-op when unconfigured). Logging NEVER swallows:
     * every caller that logs here rethrows or returns the documented
     * permissive default.
     */
    private function logError(string $message, ?Throwable $e, PeerTarget $target): void
    {
        if ($this->logger === null) {
            return;
        }

        $context = ['bucket' => $target->bucket, 'peer_type' => $target->peerType];
        if ($e !== null) {
            $context['exception'] = $e;
            $this->logger->error($message.' — '.$e->getMessage(), $context);

            return;
        }

        $this->logger->error($message, $context);
    }
}
