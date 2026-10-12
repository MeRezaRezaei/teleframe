<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Warm;

use RuntimeException;

/**
 * THE LIVE ENDPOINT POLICY — what may be exercised against a REAL Telegram session.
 *
 * The owner runs the warm pipeline against a real account, and the whole reason
 * that is safe is this file. `account.deleteAccount` is 132 methods away from
 * a typo, and MTProto has no dry-run: a method that "looks like a read" is not
 * a read until the schema says so.
 *
 * THE RULE IS DENY BY DEFAULT. A method is live-eligible only if it appears in
 * LIVE_SAFE below. Everything else — the 132 destructive ones, and the ~690
 * that are merely unclassified — is refused. Adding a method to the allowlist is
 * a deliberate act with a reason attached; removing one is always safe. The
 * asymmetry is deliberate: a wrongly-denied method costs a skipped test, a
 * wrongly-allowed method can cost the account.
 *
 * WHY SO MUCH IS DENIED. Most of the 823 methods are low-level wire operations
 * that the OFFLINE suite already covers exhaustively — 3789 PHPUnit tests with
 * no session at all, because a stubbed connection exercises encode, decode,
 * auth, retries and error mapping without Telegram's rate limiter or the account
 * itself in the loop. Live testing buys exactly three things the offline suite
 * cannot: that the session still authenticates, that the account is reachable,
 * and that a real DC answers. Everything past that is re-testing code that
 * never touched a socket.
 *
 * So the live surface is deliberately tiny, and stays tiny until MTProto itself
 * moves: a schema `layer` bump changes the wire, and that is the signal to
 * re-audit the allowlist, not a routine commit's excuse to widen it.
 *
 * WHAT IS DELIBERATELY NEVER LIVE-SAFE, even though reads exist:
 *   - anything in the DESTRUCTIVE_VERBS list below, on principle rather than
 *     per-method, so a new `messages.fooSomething` cannot slip past a reviewer
 *     who did not read the schema;
 *   - `auth.resetAuthorizations` and its neighbours, which can invalidate every
 *     other credential the pipeline depends on;
 *   - anything requiring an interactive code (phone, 2FA, password), because a
 *     runner has nobody at a keyboard to finish it and a half-finished flow
 *     leaves the session in a worse state than not starting.
 *
 * The session is a real account with a real quota. This file is the blast
 * radius boundary, and it is enforced in code so it cannot be widened by a
 * forgotten comment.
 */

final class LiveEndpointPolicy
{
    /**
     * Verbs that make a method ineligible on principle, regardless of its name's
     * apparent harmlessness. Matched against the method name after the dot.
     */
    public const DESTRUCTIVE_VERBS = [
        'delete', 'remove', 'destroy', 'forget', 'clear', 'block', 'unblock',
        'ban', 'unban', 'kick', 'promote', 'demote', 'restrict', 'edit',
        'send', 'forward', 'read', 'ack', 'reset', 'revoke', 'logout',
        'terminate', 'hide', 'archive', 'pin', 'unpin', 'close', 'open',
        'transfer', 'disconnect', 'revoke', 'set', 'save', 'upload', 'sign',
        'confirm', 'verify', 'accept', 'decline', 'reject', 'apply',
    ];

    /**
     * Methods PROVEN live-safe. Each entry carries WHY it is safe, because an
     * allowlist without a reason becomes an allowlist without a review.
     *
     * Note what is absent: nothing that writes to the account, nothing that
     * moves money or contacts, and nothing that would make the session stop
     * working at the end of the run.
     */
    public const LIVE_SAFE = [
        'users.getUsers' =>
            'Pure read: fetches a user profile for an id we already hold. No account state.',

        'users.getFullUser' =>
            'Pure read of an id we already hold. Returns a profile object; writes nothing.',

        'help.getConfig' =>
            'Pure read of the DC configuration. Cheap, and the canonical check that a session is live.',

        'help.getNearestDc' =>
            'Pure read. Answers which DC this session should talk to; no account state.',

        'auth.getPassword' =>
            'READ of the two-step state, despite the verb. Returns whether a password is set. '
            . 'It does not begin the flow and cannot invalidate the session.',

        'account.getPassword' =>
            'READ of the account two-step state. The write side (auth.resetPassword) is a '
            . 'different method and is denied by the verb rule.',
    ];

    /**
     * @return list<string> live-eligible method names
     */
    public static function liveSafeMethods(): array
    {
        return array_keys(self::LIVE_SAFE);
    }

    public static function isLiveSafe(string $method): bool
    {
        return isset(self::LIVE_SAFE[$method]);
    }

    /**
     * THE GATE. Returns null when the method may run live, or a human-readable
     * reason when it must not. Callers must refuse on a non-null reason.
     */
    public static function refusalReason(string $method): ?string
    {
        if (self::isLiveSafe($method)) {
            return null;
        }

        $verb = str_contains($method, '.') ? substr($method, strpos($method, '.') + 1) : $method;
        $lower = strtolower($verb);

        foreach (self::DESTRUCTIVE_VERBS as $dangerous) {
            if (str_starts_with($lower, $dangerous)) {
                return sprintf(
                    'method "%s" is DENIED by verb: "%s" is in the destructive verb list '
                    . '(DESTRUCTIVE_VERBS). The live surface is allowlist-only, so an '
                    . 'unlisted method is refused regardless of how harmless its name reads.',
                    $method,
                    $dangerous,
                );
            }
        }

        return sprintf(
            'method "%s" is DENIED: it is not in the live allowlist. The offline PHPUnit suite '
            . 'already covers this code path without a session; live testing here would spend '
            . 'account quota to re-test code that never touches the network. Add it to '
            . 'LiveEndpointPolicy::LIVE_SAFE with a reason ONLY if it is genuinely proven safe.',
            $method,
        );
    }

    /**
     * Filter a catalog to what the warm lane may actually call.
     *
     * @param  list<string>  $methods
     * @return list<array{method: string, allowed: bool, reason: ?string}>
     */
    public static function classify(array $methods): array
    {
        $out = [];
        foreach ($methods as $method) {
            $out[] = [
                'method' => $method,
                'allowed' => self::isLiveSafe($method),
                'reason' => self::refusalReason($method),
            ];
        }

        return $out;
    }
}
