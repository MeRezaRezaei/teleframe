<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;

/**
 * Self-originated fact classifier — the verbatim's group-2 default.
 *
 * Owner verbatim 2026-09-14: "the second type is a fact that telgram
 * produced but its a reflection of our behaviour in telegram like telegram
 * says the channel has this new message but we sent that mesage so
 * obvilusly its not going to be an input to our apps by default i asy by
 * default since some times we need to act on something special".
 *
 * When Telegram reports a message with `out` set, that message is a
 * reflection of OUR behaviour — handing it to the app as a NEW input would
 * be the update loop (each send → inbound event → maybe another send →
 * flood wait). Default: store_only.
 *
 * By default [ESCAPE HATCH]: a per-peer routing rule (tg_update_routing)
 * that explicitly marks the peer act_on flips this fact back to an input —
 * the "sometimes we need to act on something special" case. Chain-effect
 * rules (e.g. the backup timeout: absence in time, not message presence)
 * are future work and live outside this classifier.
 */
final class SelfOriginatedClassifier
{
    public function __construct(
        private readonly UpdateRouter $router,
    ) {}

    /**
     * Classify a decoded message/update payload.
     *
     * @param  array<string, mixed>  $payload  decoded TL message or update
     * @return string UpdateRoutingRule::MODE_ACT_ON | UpdateRoutingRule::MODE_STORE_ONLY
     */
    public function classify(int $accountId, array $payload): string
    {
        $isOwn = (bool) ($payload['out'] ?? false);
        if (! $isOwn) {
            // Facts from other people: completely out of our control — route
            // by the peer rule (default act_on).
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
}
