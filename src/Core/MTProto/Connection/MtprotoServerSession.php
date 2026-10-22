<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\MTProto\Connection;

use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\PacketCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLDecoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLRegistry;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLSerializer;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * MtprotoServerSession — the SERVER-direction MTProto 2.0 encoder/decoder for
 * the /apiws terminal. Plays "Telegram" to a browser-shaped client (vendored
 * GramJS over obfuscated2 + abridged framing).
 *
 * Symmetry with the client-direction engine: every inbound client envelope is
 * decrypted with PacketCodec::decryptPacket(..., fromServer: false) and every
 * outbound server envelope encrypted with PacketCodec::encryptPacket(...,
 * toServer: false) — the browser↔relay packets encrypt/decrypt correctly on
 * both legs against the same 256-byte relay auth key.
 *
 * Wire conventions owned here (the answerer may not deviate):
 *  - Server msg_id parity is ODD (msg_id & 1 == 1) — GramJS MTProtoState.ts
 *    SERVER_MSG_ID_PARITY = 1n — while the client's ids are ≡ 0 (mod 4).
 *  - Content-related server messages (rpc_result, updates push) carry an ODD,
 *    strictly increasing seq_no; service messages (pong, msgs_ack) an EVEN
 *    seq_no on a shared content counter (mirrors
 *    EncryptedConnection::nextContentSeqNo/nextContainerSeqNo). new_session_created
 *    is the one exception: the MTProto spec requires it to be CONTENT-related
 *    (odd seq_no) — GramJS MTProtoState.isMessageSequenceInvalid rejects an
 *    even-seq_no new_session_created with a SecurityError, so the opening
 *    frame is encoded with an odd seq_no like a content message.
 *  - decodeRequest unwraps msg_container (0x73F1F8DC), invokeWithLayer,
 *    initConnection and the hand-built invokeAfterMsg (0xCB9F372D, not in the
 *    registry) down to the innermost method and params, and returns one request
 *    tuple per decoded leaf method (a msg_container batch yields several).
 *
 * Byte boundary: this class deals in raw PacketCodec envelopes (the exact
 * bytes PacketCodec::encryptPacket produces). Abridged framing
 * (FrameCodec::wrapAbridgedPayload / the WS link's obfuscated2 layer) is a
 * transport concern applied outside this class.
 *
 * Zero regex: string functions / unpack only. Session strings and the auth key
 * are never logged.
 */
final class MtprotoServerSession
{
    /**
     * msg_container (bare: id + count + {msg_id, seqno, bytes, body} tuples).
     */
    private const MSG_CONTAINER = 0x73F1F8DC;

    /**
     * rpc_result req_msg_id:long result:Object. Hand-packed (the registry
     * knows the id too, but the answerer contract pins this constant).
     */
    private const RPC_RESULT = 0xF35C6D01;

    /**
     * updateShort update:Update date:int = Updates — the minimal Update push
     * container GramJS upserters understand.
     */
    private const UPDATE_SHORT = 0x78D4DEC1;

    /**
     * invokeAfterMsg msg_id:long query:!X = X — NOT in TLRegistry (absent from
     * the curated SCHEMA and the api/mtproto mirror sources), so its header is
     * peeled by raw id/length math, never run through TLDecoder.
     */
    private const INVOKE_AFTER_MSG = 0xCB9F372D;

    /**
     * Combined message payloads must stay a multiple of 4 (abridged framing
     * rule the transport relies on) — enforced at the read boundary.
     */
    private const WATERMARK_LIMIT = 2 * 1024 * 1024;

    /** @var array<int, int> msg_id of the last ping request => its ping_id (for encodePong) */
    private array $pendingPings = [];

    private int $lastMessageId = 0;

    private int $contentCounter = -1;

    public function __construct(
        private string $authKey,
        private int $sessionId,
        private int $serverSalt,
        private ?LoggerInterface $logger = null,
    ) {
        if (strlen($authKey) !== 256) {
            throw new RuntimeException(sprintf(
                'MtprotoServerSession: auth key must be 256 raw bytes, got %d',
                strlen($authKey)
            ));
        }
    }

    /**
     * The server's opening message: a packed new_session_created frame. The
     * MTProto spec treats this notification as CONTENT-related — its seq_no is
     * ODD (the first content counter slot), while pong/msgs_ack stay EVEN —
     * because GramJS MTProtoState.isMessageSequenceInvalid throws a
     * SecurityError on an even-seq_no new_session_created ("Invalid encrypted
     * packet", reconnect + HTTP fallback). Odd server msg_id; first_msg_id
     * echoes the frame's own msg_id per the common server implementation,
     * unique_id is server-chosen randomness. Sent to the client immediately
     * after the DH handshake seats the auth key.
     */
    public function serverSessionCreated(): string
    {
        $msgId = $this->nextServerMessageId();
        $uniqueId = (int) unpack('P', random_bytes(8))[1];
        $body = TLEncoder::encodeObject('new_session_created', [
            'first_msg_id' => $msgId,
            'unique_id' => $uniqueId,
            'server_salt' => $this->serverSalt,
        ]);

        return $this->encodeOutbound($body, content: true, messageId: $msgId);
    }

    /**
     * Decodes one client→server envelope. The argument is the exact output of
     * PacketCodec::encryptPacket(..., toServer: true) (the obfuscation-decrypt
     * by the WS link; no abridged length prefix — the transport owns framing).
     * Handles a plain single method, invokeWithLayer{}/initConnection{}
     * wrappers, the naked msg_container batch, and the invokeAfterMsg wrapper.
     *
     * Returns one request tuple per decoded leaf method, in wire order. For a
     * batch each tuple carries its inner msg_id/seq_no and replies to that
     * inner id; for a single message the header msg_id/seq_no is used. The
     * `original_msg_id`/`msg_id` values are the ids the answering rpc_result
     * must echo (per-request demux lives on exactly these ids in GramJS).
     *
     * @return list<array{
     *     msg_id: int,
     *     seq_no: int,
     *     method: string,
     *     params: array<string, mixed>,
     *     original_msg_id: int,
     * }>
     */
    public function decodeRequest(string $decryptedPlaintext): array
    {
        $message = PacketCodec::decryptPacket(
            $decryptedPlaintext,
            $this->authKey,
            fromServer: false,
            expectedSessionId: $this->sessionId,
        );

        $payload = $message['payload'];
        if (strlen($payload) > self::WATERMARK_LIMIT) {
            throw new RuntimeException(sprintf(
                'MtprotoServerSession: client payload of %d bytes exceeds the %d-byte limit',
                strlen($payload),
                self::WATERMARK_LIMIT
            ));
        }

        $requests = [];
        if (strlen($payload) >= 4 && unpack('V', substr($payload, 0, 4))[1] === self::MSG_CONTAINER) {
            $offset = 4;
            $count = unpack('V', substr($payload, 4, 4))[1];
            if ($count < 1 || $count > 1024) {
                throw new RuntimeException(sprintf(
                    'MtprotoServerSession: malformed msg_container with %d messages',
                    $count
                ));
            }
            $offset += 4;
            for ($i = 0; $i < $count; $i++) {
                if ($offset + 16 > strlen($payload)) {
                    throw new RuntimeException('MtprotoServerSession: truncated msg_container entry');
                }
                $msgId = unpack('P', substr($payload, $offset, 8))[1];
                $offset += 8;
                $seqNo = unpack('V', substr($payload, $offset, 4))[1];
                $offset += 4;
                $bodyLen = unpack('V', substr($payload, $offset, 4))[1];
                $offset += 4;
                if ($bodyLen < 1 || $offset + $bodyLen > strlen($payload)) {
                    throw new RuntimeException('MtprotoServerSession: malformed msg_container body length');
                }
                $requests[] = $this->decodeOne(substr($payload, $offset, $bodyLen), $msgId, $seqNo);
                $offset += $bodyLen;
            }
        } else {
            $requests[] = $this->decodeOne($payload, $message['message_id'], $message['seq_no']);
        }

        return $requests;
    }

    /**
     * Encodes the reply to one decoded request as an encrypted server→client
     * frame: rpc_result(req_msg_id = the request's id) with the vault-method's
     * decoded result re-encoded as the Object result field (content message:
     * odd seq_no, odd server msg_id).
     *
     * @param  array<string, mixed>  $result  decoded result array carrying its own '_' constructor
     */
    public function encodeResponse(int $requestMsgId, string $method, array $result): string
    {
        $body = TLSerializer::packInt(self::RPC_RESULT)
            .TLSerializer::packLong($requestMsgId)
            .$this->encodeResultObject($result);

        return $this->encodeOutbound($body, content: true);
    }

    /**
     * Encodes a server→client update push: the pre-built raw Update TL body is
     * wrapped in updateShort(update, date) (content message: odd seq_no).
     */
    public function encodeUpdate(string $updateBody): string
    {
        $body = TLSerializer::packInt(self::UPDATE_SHORT)
            .$updateBody
            .TLSerializer::packInt(time());

        return $this->encodeOutbound($body, content: true);
    }

    /**
     * Encodes the service-message pong answering a client's ping. The echoed
     * ping_id is whatever decodeRequest recorded for that request msg_id when
     * it unwrapped ping_delay_disconnect (service message: even seq_no).
     */
    public function encodePong(int $msgId): string
    {
        $pingId = $this->pendingPings[$msgId] ?? 0;
        $body = TLEncoder::encodeObject('pong', [
            'msg_id' => $msgId,
            'ping_id' => $pingId,
        ]);

        return $this->encodeOutbound($body, content: false);
    }

    /**
     * Encodes a service-message msgs_ack acknowledging the given message ids
     * (service message: even seq_no).
     */
    public function encodeAck(int ...$msgIds): string
    {
        $body = TLEncoder::encodeObject('msgs_ack', ['msg_ids' => $msgIds]);

        return $this->encodeOutbound($body, content: false);
    }

    /**
     * @return array{
     *     msg_id: int,
     *     seq_no: int,
     *     method: string,
     *     params: array<string, mixed>,
     *     original_msg_id: int,
     * }
     */
    private function decodeOne(string $body, int $msgId, int $seqNo): array
    {
        $body = $this->peelInvokeAfterMsg($body);

        $offset = 0;
        $decoded = TLDecoder::decodeObject($body, $offset);

        while (true) {
            $name = (string) ($decoded['_'] ?? '');
            if ($name !== 'invokeWithLayer' && $name !== 'initConnection') {
                break;
            }
            $query = $decoded['query'] ?? null;
            if (! is_array($query)) {
                throw new RuntimeException("MtprotoServerSession: {$name} wrapper without a query");
            }
            $decoded = $query;
        }

        $method = (string) ($decoded['_'] ?? '');
        if ($method === '') {
            throw new RuntimeException('MtprotoServerSession: request body does not decode to an RPC method');
        }
        unset($decoded['_']);

        if ($method === 'ping' || $method === 'ping_delay_disconnect') {
            $this->pendingPings[$msgId] = (int) ($decoded['ping_id'] ?? 0);
        }

        $this->logger?->debug('teleframe.mtproto.session.decode', [
            'method' => $method,
            'msg_id' => $msgId,
        ]);

        return [
            'msg_id' => $msgId,
            'seq_no' => $seqNo,
            'method' => $method,
            'params' => $decoded,
            'original_msg_id' => $msgId,
        ];
    }

    /**
     * Strips any leading invokeAfterMsg wrapper(s) by raw id math: the wrapper
     * is id(4) + msg_id(8) + query, and it is NOT registered in TLRegistry so
     * it can never go through TLDecoder. Nested inside registered generics (an
     * invokeAfterMsg around invokeWithLayer) is handled because this is applied
     * before TLDecoder sees the bytes.
     */
    private function peelInvokeAfterMsg(string $body): string
    {
        while (strlen($body) >= 4) {
            $cid = unpack('V', substr($body, 0, 4))[1];
            if ($cid !== self::INVOKE_AFTER_MSG) {
                break;
            }
            $body = substr($body, 12);
        }

        return $body;
    }

    /**
     * Encodes the `Object` field of an rpc_result into TL bytes.
     *
     * The registry can only re-encode constructors it knows by name, so two
     * result shapes are handled here before falling back to TLEncoder:
     *
     *  - '_' => 'Vector' (root-vector results such as users.getUsers): the
     *    bare polymorphic Vector<T> (0x1cb5c415) has no registered name and
     *    TLEncoder::encodeObject would throw, so the wrapper is hand-packed
     *    and each item encoded via encodeDecodedObject.
     *  - '_' => 'preencoded': a raw TL object body produced by the answerer,
     *    the browser-layer escape hatch for constructor ids the local registry
     *    cannot express (nested objects cannot reach an id override through
     *    TLEncoder's nested-object branch).
     *
     * @param  array<string, mixed>  $result
     */
    private function encodeResultObject(array $result): string
    {
        $name = (string) ($result['_'] ?? '');
        if ($name === '') {
            throw new RuntimeException('MtprotoServerSession: rpc result array is missing its "_" constructor');
        }

        if ($name === 'Vector') {
            return TLSerializer::packVector(
                (array) ($result['items'] ?? []),
                static fn (mixed $item): string => self::encodeDecodedObject($item),
            );
        }

        if ($name === 'preencoded') {
            if (! is_string($result['body'] ?? null) || $result['body'] === '') {
                throw new RuntimeException('MtprotoServerSession: preencoded rpc result is missing its "body"');
            }

            return $result['body'];
        }

        return TLEncoder::encodeObject($name, $result);
    }

    /**
     * Encodes one decoded object for a root vector, honoring an explicit
     * browser-layer constructor id ('_id') where the local registry carries
     * the constructor under a different id (layers disagree): the TLEncoder
     * output is re-suffixed with the caller's id so the browser decodes it.
     * Scalar results are untouched by this path.
     *
     * @param  mixed  $item  decoded object (array carrying '_')
     */
    private static function encodeDecodedObject(mixed $item): string
    {
        if (! is_array($item) || ! is_string($item['_'] ?? null) || $item['_'] === '') {
            throw new RuntimeException('MtprotoServerSession: root-vector items must be decoded objects carrying "_"');
        }

        $body = TLEncoder::encodeObject($item['_'], $item);
        if (is_int($item['_id'] ?? null) && $item['_id'] !== TLRegistry::id($item['_'])) {
            return TLSerializer::packInt($item['_id']).substr($body, 4);
        }

        return $body;
    }

    private function encodeOutbound(string $body, bool $content, ?int $messageId = null): string
    {
        return PacketCodec::encryptPacket(
            payload: $body,
            authKey: $this->authKey,
            sessionId: $this->sessionId,
            serverSalt: $this->serverSalt,
            seqNo: $content ? $this->nextContentSeqNo() : $this->nextServiceSeqNo(),
            toServer: false,
            messageId: $messageId ?? $this->nextServerMessageId(),
        );
    }

    /**
     * Server-direction message id: ODD (parity bit set — GramJS
     * SERVER_MSG_ID_PARITY = 1n), time-based, ≡ 1 (mod 8) when first generated
     * and strictly increasing afterwards (stepping 8 keeps the residue class).
     */
    private function nextServerMessageId(): int
    {
        $candidate = (((int) (microtime(true) * 2 ** 32)) & ~7) | 1;
        if ($candidate <= $this->lastMessageId) {
            $candidate = $this->lastMessageId + 8;
        }

        return $this->lastMessageId = $candidate;
    }

    /**
     * seq_no for outgoing server CONTENT messages (rpc_result, update push):
     * odd and strictly increasing, exactly like the client-direction rule.
     */
    private function nextContentSeqNo(): int
    {
        return $this->contentCounter += 2;
    }

    /**
     * seq_no for outgoing server SERVICE messages (new_session_created, pong,
     * msgs_ack): even, content counter NOT consumed — mirrors
     * EncryptedConnection::nextContainerSeqNo.
     */
    private function nextServiceSeqNo(): int
    {
        return $this->contentCounter + 1;
    }
}
