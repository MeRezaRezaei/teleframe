<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Backup;

use MeRezaRezaei\Teleframe\Store\ObjectManifest;

/**
 * PeerTarget (remainder design §2/§6/§8, cycle 11 wave 2) — one backup
 * destination: a channel OR a supergroup (megagroup) peer. Both are
 * `channel` TL objects and both dial as inputPeerChannel (design §6), so
 * the existing TelegramStoreChannel send path (sendDocument/sendMessage
 * with a peer array) works for both unchanged — what changes is
 * membership semantics (wave-2 PeerMembership), not the wire.
 *
 * RULING (never a session string): a PeerTarget carries ids / access
 * hashes / usernames ONLY — never session strings, never DB credentials.
 * memberAccounts holds user ids/usernames for the wave-2 invite seam
 * (channels.inviteToChannel). The passphrase — the "put pass on whatever
 * you send there" seal secret — is carried in memory but NEVER
 * serialized: toRow() emits only a has_passphrase bool (design §11:
 * session strings and secrets never reach rows/logs/Telegram-visible
 * text; the config row keeps the secret, the row does not).
 *
 * RULING (seal): member/public targets REQUIRE sealed (design §6).
 * requiresSeal() = peerType === 'group' || mode === MODE_SEALED — a
 * group is a member-scoped target, so it always requires seal; an owned
 * channel defaults sealed anyway.
 *
 * Pure data — readonly ctor, inputPeer()/toRow()/requiresSeal(). Zero
 * regex (Store\Backup is phpstan-guarded; preg_* is banned here).
 *
 * Provenance:
 * docs/superpowers/plans/2026-09-24-backup-store-remainder-design.md §2
 * (PeerTarget row), §6 (channel/group target RULINGS table + seal rule),
 * §8 (wave 2 — cycle 11 wave 2-A).
 */
final class PeerTarget
{
    /**
     * @param  int|string  $peerId  channel/supergroup peer id; string accepted
     *                              for oversized/legacy ids — the wire always
     *                              casts to int channel_id
     * @param  'channel'|'group'  $peerType  'channel' (owned/broadcast) or
     *                                       'group' (supergroup/megagroup)
     * @param  string  $mode  ObjectManifest::MODE_RAW | MODE_SEALED (sealed default)
     * @param  list<int|string>  $memberAccounts  user ids/usernames for the
     *                                            wave-2 invite seam — never
     *                                            session strings
     */
    public function __construct(
        public readonly string $bucket,
        public readonly int|string $peerId,
        public readonly ?int $accessHash = null,
        public readonly string $peerType = 'channel',
        public readonly string $mode = ObjectManifest::MODE_SEALED,
        public readonly ?string $passphrase = null,
        public readonly array $memberAccounts = [],
    ) {}

    /**
     * The MTProto input peer for sendDocument/sendMessage/… — ALWAYS
     * inputPeerChannel, for BOTH peer types: a supergroup is a `channel`
     * TL object and dials exactly like an owned broadcast channel
     * (design §6), so the existing TelegramStoreChannel::inputPeer()
     * peer-array shape {_: 'inputPeerChannel', channel_id, access_hash}
     * is reused verbatim.
     *
     * RULING (absent access hash — wave-2 reconcile): a null/absent
     * accessHash renders as 0, the MTProto "unknown hash" sentinel. That
     * shape is VALID for the invitation/join/membership flows this seam
     * serves — channels.inviteToChannel, channels.joinChannel and the
     * getParticipants probe identify the peer by id, and the hash is not
     * required server-side for those calls. Uploads (sendDocument /
     * sendMessage) to a hash-0 peer may be rejected until the hash is
     * learned from a dialog walk — callers own supplying it: config
     * target rows carry access_hash when known
     * (PeerTargetRegistry maps it), and the dialog-walk classification
     * seam fills it before upload.
     *
     * @return array{_: string, channel_id: int, access_hash: int}
     */
    public function inputPeer(): array
    {
        return [
            '_' => 'inputPeerChannel',
            'channel_id' => (int) $this->peerId,
            'access_hash' => (int) ($this->accessHash ?? 0),
        ];
    }

    /**
     * The truth-adjacent row for this target (registry / tf_store_buckets
     * surfacing). NEVER carries the passphrase — only has_passphrase, so
     * a caller knows the seal secret is configured without the secret
     * ever leaving the config row.
     *
     * @return array{bucket: string, peer_id: int|string, peer_type: string, mode: string, has_passphrase: bool}
     */
    public function toRow(): array
    {
        return [
            'bucket' => $this->bucket,
            'peer_id' => $this->peerId,
            'peer_type' => $this->peerType,
            'mode' => $this->mode,
            'has_passphrase' => $this->passphrase !== null && $this->passphrase !== '',
        ];
    }

    /**
     * Whether this target MUST be uploaded sealed. RULING (design §6):
     * sealed is REQUIRED on member/public targets — "put pass on
     * whatever you send there". A group is inherently member/public, so
     * any group requires seal; an owned channel requires seal when its
     * mode is the sealed default (raw on a group is a config error, not
     * a runtime surprise — §11 config merge).
     */
    public function requiresSeal(): bool
    {
        return $this->peerType === 'group' || $this->mode === ObjectManifest::MODE_SEALED;
    }
}
