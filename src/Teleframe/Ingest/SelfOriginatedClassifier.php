<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use MeRezaRezaei\Teleframe\Handler\Middleware\EchoEliminator;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;
use Psr\SimpleCache\CacheInterface;

/**
 * Self-originated fact classifier — the verbatim's group-2 default.
 *
 * Owner verbatim 2026-09-14: "the second type is a fact that telgram
 * produced but its a reflection of our behaviour in telegram like telegram
 * says the channel has this new message but we sent that mesage so
 * obvilusly its not going to be an input to our apps by default i asy by
 * default since some times we need to act on something special".
 *
 * Two self-origination signals, strongest first:
 *
 * 1. The send-time registry (Q2d PSR-16 cache written by the facade's send
 *    path — random_id/msg_id). Telethon's own docs flag the `out` flag as
 *    NOT reliable in broadcast channels, so a registry match is the
 *    authoritative "we sent this" evidence. Matching an own send marks the
 *    fact a reflection of our behaviour → store_only (no re-input loop).
 *
 * 2. The `out` flag — Telegram's own marker, reliable outside broadcast
 *    channels.
 *
 * Escapes (per-peer routing rules) apply to both signals: an in-registry
 * or out-flagged fact whose peer is explicitly act_on becomes an input —
 * the "sometimes we need to act on something special" / chain-effect case.
 */
final class SelfOriginatedClassifier
{
    public function __construct(
        private readonly UpdateRouter $router,
        private readonly ?CacheInterface $sends = null,
    ) {}

    /**
     * Classify a decoded message/update payload.
     *
     * @param  array<string, mixed>  $payload  decoded TL message or update
     * @return string UpdateRoutingRule::MODE_ACT_ON | UpdateRoutingRule::MODE_STORE_ONLY
     */
    public function classify(int $accountId, array $payload): string
    {
        $isOwn = $this->registryMatchesOwnSend($accountId, $payload)
            || (bool) ($payload['out'] ?? false);

        if (! $isOwn) {
            // Facts from other people: completely out of our control — route
            // by the peer rule (default store_only; verbatim 2026-09-14).
            return $this->router->classify($accountId, $payload);
        }

        $peerType = (int) ($payload['peer_id']['_type'] ?? 0);
        $peerId = (int) ($payload['peer_id']['_id'] ?? 0);

        // Escape hatch: an explicit per-peer rule wins; the default for
        // self-originated facts is store_only (no re-input loop).
        if ($peerType !== 0 && $peerId !== 0) {
            $peerMode = $this->router->explicitMode($accountId, $peerType, $peerId);
            if ($peerMode !== null) {
                return $peerMode;
            }
        }

        return UpdateRoutingRule::MODE_STORE_ONLY;
    }

    /**
     * Strong self-origination evidence: the facade's send-time registry has
     * an entry for this account with this update's random_id or msg_id — we
     * literally just sent it. Reliable where `out` is not (broadcast
     * channels).
     *
     * @param  array<string, mixed>  $payload
     */
    private function registryMatchesOwnSend(int $accountId, array $payload): bool
    {
        if ($this->sends === null) {
            return false;
        }

        foreach (['random_id', 'msg_id'] as $field) {
            $value = (string) ($payload[$field] ?? '');
            if ($value === '') {
                continue;
            }
            $record = $this->sends->get(
                EchoEliminator::KEY.':'.$accountId.':'.$value
            );
            if (is_array($record)) {
                return true;
            }
        }

        return false;
    }
}
