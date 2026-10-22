<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\MTProto\Connection;

use MeRezaRezaei\Teleframe\Core\MTProto\Connection\EncryptedConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\Connection\MtprotoServerSession;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\PacketCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLDecoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLSerializer;
use PHPUnit\Framework\TestCase;

/**
 * Server-direction MTProto session codec, proven purely offline against a
 * synthetic 256-byte browser↔relay key (no live credentials). The client leg
 * is recreated with the engine's own PacketCodec directional encryption, the
 * same way the wire-proof plan drives VaultMtprotoAnswererTest.
 */
final class MtprotoServerSessionTest extends TestCase
{
    private const MSG_CONTAINER = 0x73F1F8DC;

    private function session(string $key, int $sessionId, int $serverSalt): MtprotoServerSession
    {
        return new MtprotoServerSession($key, $sessionId, $serverSalt);
    }

    /** A synthetic browser↔relay auth key: 256 raw bytes (mtproto auth key size). */
    private function authKey(): string
    {
        return random_bytes(256);
    }

    /**
     * The browser-shaped client encrypts one abridged-plain envelope exactly
     * as it would for a real DC (toServer: true, client msg_id ≡ 0 mod 4).
     */
    private function clientEnvelope(string $body, int $msgId, int $seqNo, string $key, int $sessionId, int $serverSalt): string
    {
        return PacketCodec::encryptPacket(
            payload: $body,
            authKey: $key,
            sessionId: $sessionId,
            serverSalt: $serverSalt,
            seqNo: $seqNo,
            toServer: true,
            messageId: $msgId,
        );
    }

    private function clientTimeBase(): int
    {
        return ((int) (microtime(true) * 2 ** 32)) & ~3;
    }

    /**
     * invokeWithLayer(initConnection(query)) as the engine's own client builds
     * its first query (EncryptedConnection::buildFirstQueryBody).
     */
    private function layeredBody(array $query): string
    {
        return EncryptedConnection::buildFirstQueryBody(227, [
            'api_id' => 1234,
            'device_model' => 'PHPUnit',
            'system_version' => 'Linux',
            'app_version' => '1.0.0',
            'system_lang_code' => 'en',
            'lang_pack' => '',
            'lang_code' => 'en',
            'query' => $query,
        ]);
    }

    public function test_server_session_created_frames_a_valid_new_session(): void
    {
        $key = $this->authKey();
        $sessionId = random_int(1, PHP_INT_MAX);
        $serverSalt = random_int(1, PHP_INT_MAX);
        $session = $this->session($key, $sessionId, $serverSalt);

        $frame = $session->serverSessionCreated();

        $message = PacketCodec::decryptPacket($frame, $key, fromServer: true, expectedSessionId: $sessionId);
        $this->assertSame(1, $message['message_id'] & 1, 'server msg_id must be ODD (GramJS SERVER_MSG_ID_PARITY = 1n)');
        $this->assertSame(1, $message['seq_no'] & 1, 'new_session_created is CONTENT-related with ODD seq_no (GramJS MTProtoState.isMessageSequenceInvalid rejects an even seq_no)');
        $this->assertSame($serverSalt, $message['server_salt']);

        $offset = 0;
        $decoded = TLDecoder::decodeObject($message['payload'], $offset);
        $this->assertSame('new_session_created', $decoded['_']);
        $this->assertSame($message['message_id'], $decoded['first_msg_id']);
        $this->assertSame($serverSalt, $decoded['server_salt']);
        $this->assertSame($sessionId, $message['session_id']);
    }

    public function test_round_trip_request_response_matches_req_msg_id(): void
    {
        $key = $this->authKey();
        $sessionId = random_int(1, PHP_INT_MAX);
        $serverSalt = random_int(1, PHP_INT_MAX);
        $session = $this->session($key, $sessionId, $serverSalt);

        $clientId = $this->clientTimeBase();
        $request = $this->layeredBody(['_' => 'help.getConfig']);
        $envelope = $this->clientEnvelope($request, $clientId, 1, $key, $sessionId, $serverSalt);

        $decoded = $session->decodeRequest($envelope);
        $this->assertCount(1, $decoded, 'a single-message envelope must decode to exactly ONE request');
        $this->assertSame($clientId, $decoded[0]['msg_id'], 'the decoded msg_id must be the id the client sent');
        $this->assertSame($clientId, $decoded[0]['original_msg_id'], 'original_msg_id must equal msg_id (no resend yet)');
        $this->assertSame(1, $decoded[0]['seq_no'], 'the first content message must carry seq_no 1');
        $this->assertSame('help.getConfig', $decoded[0]['method'], 'the decoded method must be the help.getConfig the client wrapped in invokeWithLayer');
        $this->assertSame([], $decoded[0]['params'], 'help.getConfig takes no params, so the decoded params must be an empty array');

        $reply = $session->encodeResponse($decoded[0]['original_msg_id'], 'help.getConfig', ['_' => 'boolTrue']);

        $message = PacketCodec::decryptPacket($reply, $key, fromServer: true, expectedSessionId: $sessionId);
        $this->assertSame(1, $message['message_id'] & 1, 'server rpc_result msg_id must be ODD');
        $this->assertSame(1, $message['seq_no'] & 1, 'rpc_result is a CONTENT message with ODD seq_no');

        $offset = 0;
        $rpc = TLDecoder::decodeObject($message['payload'], $offset);
        $this->assertSame('rpc_result', $rpc['_'], 'the reply payload must decode as an rpc_result');
        $this->assertSame($clientId, $rpc['req_msg_id'], 'rpc_result must echo the request msg_id');
        $this->assertIsArray($rpc['result'], 'the rpc_result result must be a decoded object, not a raw id');
        $this->assertSame('boolTrue', $rpc['result']['_'], 'the rpc_result result must be the boolTrue that was encoded');
    }

    public function test_request_with_invoke_after_msg_wrapper_is_unwrapped(): void
    {
        $key = $this->authKey();
        $sessionId = random_int(1, PHP_INT_MAX);
        $serverSalt = random_int(1, PHP_INT_MAX);
        $session = $this->session($key, $sessionId, $serverSalt);

        $inner = TLEncoder::encodeObject('help.getNearestDc', []);
        $wrapped = TLSerializer::packInt(0xCB9F372D).TLSerializer::packLong(424242).$inner;

        $clientId = $this->clientTimeBase();
        $envelope = $this->clientEnvelope($wrapped, $clientId, 1, $key, $sessionId, $serverSalt);

        $decoded = $session->decodeRequest($envelope);
        $this->assertCount(1, $decoded);
        $this->assertSame('help.getNearestDc', $decoded[0]['method']);
        $this->assertSame($clientId, $decoded[0]['original_msg_id']);
    }

    public function test_msg_container_with_invoke_with_layer_batches_decode_both_methods(): void
    {
        $key = $this->authKey();
        $sessionId = random_int(1, PHP_INT_MAX);
        $serverSalt = random_int(1, PHP_INT_MAX);
        $session = $this->session($key, $sessionId, $serverSalt);

        $m1 = $this->layeredBody(['_' => 'help.getNearestDc']);
        $m2 = $this->layeredBody([
            '_' => 'users.getUsers',
            'id' => [['_' => 'inputUserSelf']],
        ]);
        $base = $this->clientTimeBase();
        $container = TLSerializer::packInt(self::MSG_CONTAINER).TLSerializer::packInt(2)
            .TLSerializer::packLong($base).TLSerializer::packInt(1).TLSerializer::packInt(strlen($m1)).$m1
            .TLSerializer::packLong($base + 4).TLSerializer::packInt(3).TLSerializer::packInt(strlen($m2)).$m2;

        $envelope = $this->clientEnvelope($container, $base + 8, 2, $key, $sessionId, $serverSalt);

        $decoded = $session->decodeRequest($envelope);
        $this->assertCount(2, $decoded, 'the naked container must decode to exactly TWO requests');

        $this->assertSame('help.getNearestDc', $decoded[0]['method'], 'container SLOT 0 must be help.getNearestDc, the FIRST inner body');
        $this->assertSame([], $decoded[0]['params'], 'container SLOT 0 (help.getNearestDc) takes no params');
        $this->assertSame($base, $decoded[0]['msg_id'], 'container SLOT 0 must carry the FIRST inner msg_id');
        $this->assertSame($base, $decoded[0]['original_msg_id'], 'container SLOT 0 original_msg_id must equal its msg_id (no resend yet)');
        $this->assertSame(1, $decoded[0]['seq_no'], 'inner content messages keep their own odd seq_no');

        $this->assertSame('users.getUsers', $decoded[1]['method'], 'container SLOT 1 must be users.getUsers, the SECOND inner body');
        $this->assertSame([['_' => 'inputUserSelf']], $decoded[1]['params']['id'], 'container SLOT 1 must carry its inputUserSelf id vector');
        $this->assertSame($base + 4, $decoded[1]['msg_id'], 'container SLOT 1 must carry the SECOND inner msg_id');
        $this->assertSame($base + 4, $decoded[1]['original_msg_id'], 'container SLOT 1 original_msg_id must equal its msg_id (no resend yet)');

        $reply = $session->encodeResponse($decoded[1]['original_msg_id'], 'users.getUsers', ['_' => 'boolTrue']);
        $message = PacketCodec::decryptPacket($reply, $key, fromServer: true, expectedSessionId: $sessionId);
        $offset = 0;
        $rpc = TLDecoder::decodeObject($message['payload'], $offset);
        $this->assertSame('rpc_result', $rpc['_'], 'the reply must be an rpc_result');
        $this->assertSame($base + 4, $rpc['req_msg_id'], 'batch sibling answers its own inner msg_id');
        $this->assertSame('boolTrue', $rpc['result']['_'], 'the reply result must be the boolTrue the test asked for');
    }

    public function test_pong_echoes_recorded_ping_id(): void
    {
        $key = $this->authKey();
        $sessionId = random_int(1, PHP_INT_MAX);
        $serverSalt = random_int(1, PHP_INT_MAX);
        $session = $this->session($key, $sessionId, $serverSalt);

        $pingId = 0xDEADBEEF;
        $body = TLEncoder::encodeObject('ping_delay_disconnect', [
            'ping_id' => $pingId,
            'disconnect_delay' => 50,
        ]);
        $clientId = $this->clientTimeBase();
        $envelope = $this->clientEnvelope($body, $clientId, 1, $key, $sessionId, $serverSalt);

        $decoded = $session->decodeRequest($envelope);
        $this->assertSame('ping_delay_disconnect', $decoded[0]['method']);
        $this->assertSame($pingId, $decoded[0]['params']['ping_id']);

        $frame = $session->encodePong($decoded[0]['original_msg_id']);
        $message = PacketCodec::decryptPacket($frame, $key, fromServer: true, expectedSessionId: $sessionId);
        $this->assertSame(0, $message['seq_no'] & 1, 'pong is a SERVICE message with EVEN seq_no');

        $offset = 0;
        $pong = TLDecoder::decodeObject($message['payload'], $offset);
        $this->assertSame('pong', $pong['_']);
        $this->assertSame($clientId, $pong['msg_id']);
        $this->assertSame($pingId, $pong['ping_id']);
    }

    public function test_ack_encodes_msgs_ack_vector(): void
    {
        $key = $this->authKey();
        $sessionId = random_int(1, PHP_INT_MAX);
        $serverSalt = random_int(1, PHP_INT_MAX);
        $session = $this->session($key, $sessionId, $serverSalt);

        $frame = $session->encodeAck(111, 222, 333);
        $message = PacketCodec::decryptPacket($frame, $key, fromServer: true, expectedSessionId: $sessionId);
        $this->assertSame(0, $message['seq_no'] & 1, 'msgs_ack is a SERVICE message with EVEN seq_no');

        $offset = 0;
        $ack = TLDecoder::decodeObject($message['payload'], $offset);
        $this->assertSame('msgs_ack', $ack['_']);
        $this->assertSame([111, 222, 333], $ack['msg_ids']);
    }

    public function test_update_push_encodes_update_short_container(): void
    {
        $key = $this->authKey();
        $sessionId = random_int(1, PHP_INT_MAX);
        $serverSalt = random_int(1, PHP_INT_MAX);
        $session = $this->session($key, $sessionId, $serverSalt);

        $updateBody = TLEncoder::encodeObject('updateChannel', ['channel_id' => 999]);
        $frame = $session->encodeUpdate($updateBody);

        $message = PacketCodec::decryptPacket($frame, $key, fromServer: true, expectedSessionId: $sessionId);
        $this->assertSame(1, $message['seq_no'] & 1, 'update push is a CONTENT message with ODD seq_no');

        $offset = 0;
        $push = TLDecoder::decodeObject($message['payload'], $offset);
        $this->assertSame('updateShort', $push['_']);
        $this->assertSame(['_' => 'updateChannel', 'channel_id' => 999], $push['update']);
        $this->assertGreaterThan(0, $push['date']);
    }

    public function test_server_message_ids_stay_odd_and_strictly_increasing(): void
    {
        $key = $this->authKey();
        $sessionId = random_int(1, PHP_INT_MAX);
        $serverSalt = random_int(1, PHP_INT_MAX);
        $session = $this->session($key, $sessionId, $serverSalt);

        $last = 0;
        for ($i = 0; $i < 10; $i++) {
            $frame = $session->encodePong($i);
            $message = PacketCodec::decryptPacket($frame, $key, fromServer: true);
            $this->assertSame(1, $message['message_id'] & 1, 'server msg_id must be ODD');
            $this->assertGreaterThan($last, $message['message_id'], 'server msg_id must be strictly increasing');
            $last = $message['message_id'];
        }
    }

    public function test_server_frames_obey_gramjs_sequence_rules(): void
    {
        // Replays GramJS MTProtoState.validateIncomingMessages over a realistic
        // server-side frame stream (the /apiws terminal's exact write pattern):
        // new_session_created -> [pong | rpc_result]* -> msgs_ack. The opening
        // new_session_created MUST be content-related (odd seq_no) or GramJS
        // throws a SecurityError ("Invalid encrypted packet"). Every server
        // msg_id must be ODD and strictly increasing and every seq_no strictly
        // increasing across adjacent (msg_id-ordered) messages.
        $key = $this->authKey();
        $sessionId = random_int(1, PHP_INT_MAX);
        $serverSalt = random_int(1, PHP_INT_MAX);
        $session = $this->session($key, $sessionId, $serverSalt);

        $clientId = $this->clientTimeBase();
        $request = $this->layeredBody(['_' => 'help.getConfig']);
        $decoded = $session->decodeRequest($this->clientEnvelope($request, $clientId, 1, $key, $sessionId, $serverSalt));

        $frames = [$session->serverSessionCreated()];

        foreach ($decoded as $req) {
            $frames[] = $session->encodeResponse($req['original_msg_id'], 'help.getConfig', ['_' => 'boolTrue']);
        }
        $frames[] = $session->encodeAck($clientId);

        $lastMsgId = 0;
        $lastContentSeq = 0;
        $lastServiceSeq = 0;
        foreach ($frames as $frame) {
            $message = PacketCodec::decryptPacket($frame, $key, fromServer: true, expectedSessionId: $sessionId);
            $msgId = (int) $message['message_id'];
            $seqNo = (int) $message['seq_no'];
            $offset = 0;
            $obj = TLDecoder::decodeObject($message['payload'], $offset);
            $ctor = (string) ($obj['_'] ?? '?');

            $this->assertSame(1, $msgId & 1, "{$ctor} msg_id must be ODD");
            $this->assertGreaterThan($lastMsgId, $msgId, "{$ctor} msg_id must be strictly increasing");
            $lastMsgId = $msgId;

            if ($ctor === 'new_session_created') {
                $this->assertSame(1, $seqNo & 1, 'new_session_created must be content-related (odd seq_no), GramJS isMessageSequenceInvalid rejects even');
            }
            if ($ctor === 'msgs_ack' || $ctor === 'pong') {
                $this->assertSame(0, $seqNo & 1, "{$ctor} must be a service message (even seq_no)");
                $this->assertGreaterThan($lastServiceSeq, $seqNo, "{$ctor} seq_no must be strictly increasing (ordered by msg_id)");
                $lastServiceSeq = $seqNo;
            } else {
                $this->assertSame(1, $seqNo & 1, "{$ctor} must be a content message (odd seq_no)");
                $this->assertGreaterThan($lastContentSeq, $seqNo, "{$ctor} seq_no must be strictly increasing (ordered by msg_id)");
                $lastContentSeq = $seqNo;
            }
            $this->assertGreaterThanOrEqual(0, $seqNo);
        }

        // Adjacent-order sanity: service seq must sit BETWEEN the content seqs
        // around it, exactly the increasing 1,2,3,4... the GramJS adjacency
        // check (prev > next || prev === next odd) needs.
        $this->assertSame(1, (int) PacketCodec::decryptPacket($frames[0], $key, fromServer: true)['seq_no']);
        $this->assertCount(3, $frames, 'nsc + one rpc_result + one msgs_ack');
    }

    public function test_rejects_auth_key_that_is_not256_bytes(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('256 raw bytes');
        new MtprotoServerSession(random_bytes(16), 1, 1);
    }
}
