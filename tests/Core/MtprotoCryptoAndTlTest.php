<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Tests;

use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\AesIge;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\AuthKeyFactory;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\PacketCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\PasswordCalculator;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLSerializer;
use MeRezaRezaei\Teleframe\Core\Types\InputChannel;
use MeRezaRezaei\Teleframe\Core\Types\InputFile;
use MeRezaRezaei\Teleframe\Core\Types\InputUser;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;

class MtprotoCryptoAndTlTest extends TestCase
{
    public function test_aes_ige_encrypt_and_decrypt(): void
    {
        $key = random_bytes(32);
        $iv = random_bytes(32);
        // IGE inputs must be block-aligned (PacketCodec pads before calling)
        $plaintext = str_pad('Secret Telegram MTProto message payload 1234567890!', 64, '.');

        $cipher = AesIge::encrypt($plaintext, $key, $iv);
        $this->assertSame(strlen($plaintext), strlen($cipher));
        $decrypted = AesIge::decrypt($cipher, $key, $iv);

        $this->assertSame($plaintext, $decrypted);
    }

    public function test_tl_string_packing_and_unpacking(): void
    {
        $testStrings = [
            'short',
            'Hello world! A bit longer string that spans several words.',
            str_repeat('A', 300), // > 253 bytes to trigger 4-byte length prefix
        ];

        foreach ($testStrings as $original) {
            $packed = TLSerializer::packString($original);
            $offset = 0;
            $unpacked = TLSerializer::unpackString($packed, $offset);

            $this->assertEquals($original, $unpacked);
            $this->assertEquals(strlen($packed), $offset);
        }
    }

    public function test_password_calculator_srp_proof(): void
    {
        $accountPassword = [
            'has_password' => true,
            'current_algo' => [
                'g' => 3,
                'p' => self::dhPrime(),
                'salt1' => 'salt1_test_bytes',
                'salt2' => 'salt2_test_bytes',
            ],
            'srp_B' => str_repeat("\xBB", 256),
            'srp_id' => 123456789,
        ];

        $proof = PasswordCalculator::computeSrpProof(
            $accountPassword,
            'my_secure_cloud_password'
        );

        $this->assertEquals(123456789, $proof['srp_id']);
        $this->assertNotEmpty($proof['A']);
        $this->assertEquals(32, strlen($proof['M1']));
    }

    public function test_tl_vector_and_primitives(): void
    {
        $numbers = [10, 20, 30, 40, 50];
        $packed = TLSerializer::packVector($numbers, fn ($n) => TLSerializer::packInt($n));

        $offset = 0;
        $unpacked = TLSerializer::unpackVector($packed, $offset, fn ($buf, &$off) => TLSerializer::unpackInt($buf, $off));

        $this->assertEquals($numbers, $unpacked);
        $this->assertEquals(strlen($packed), $offset);

        // Double
        $d = 3.1415926535;
        $packedD = TLSerializer::packDouble($d);
        $offD = 0;
        $unpackedD = TLSerializer::unpackDouble($packedD, $offD);
        $this->assertEqualsWithDelta($d, $unpackedD, 0.00001);
    }

    public function test_mt_proto_packet_codec_encryption_and_decryption(): void
    {
        $authKey = random_bytes(256);
        $sessionId = 1234567890;
        $serverSalt = 9876543210;
        $seqNo = 2;
        $payload = TLSerializer::packString('MTProto 2.0 Binary Wire Packet Payload');

        // Client -> Server packet
        $packet = PacketCodec::encryptPacket(
            payload: $payload,
            authKey: $authKey,
            sessionId: $sessionId,
            serverSalt: $serverSalt,
            seqNo: $seqNo,
            toServer: true
        );

        $this->assertGreaterThan(40, strlen($packet));

        $decrypted = PacketCodec::decryptPacket(
            $packet,
            $authKey,
            fromServer: false
        );

        $this->assertEquals($sessionId, $decrypted['session_id']);
        $this->assertEquals($serverSalt, $decrypted['server_salt']);
        $this->assertEquals($seqNo, $decrypted['seq_no']);
        $this->assertEquals($payload, $decrypted['payload']);
    }

    public function test_mt_proto_input_types_builders(): void
    {
        $userInput = InputUser::user(12345, 123456789012345678);
        $this->assertEquals('inputUser', $userInput['_']);
        $this->assertEquals(12345, $userInput['user_id']);
        $this->assertSame(123456789012345678, $userInput['access_hash']);

        $channelInput = InputChannel::channel(67890, 876543210987654321);
        $this->assertEquals('inputChannel', $channelInput['_']);
        $this->assertEquals(67890, $channelInput['channel_id']);
        $this->assertSame(876543210987654321, $channelInput['access_hash']);

        $fileInput = InputFile::file(111, 4, 'photo.jpg', 'md5_sum');
        $this->assertEquals('inputFile', $fileInput['_']);
        $this->assertEquals(4, $fileInput['parts']);

        $bigFileInput = InputFile::big(222, 100, 'video.mp4');
        $this->assertEquals('inputFileBig', $bigFileInput['_']);
        $this->assertEquals(100, $bigFileInput['parts']);
    }

    // --- decryptPacket post-decrypt validation hardening (audit fix #3) ---

    public function test_decrypt_packet_accepts_expected_session_id_when_matching(): void
    {
        [$packet, $authKey, $sessionId] = $this->craftValidPacket();

        $decrypted = PacketCodec::decryptPacket(
            $packet,
            $authKey,
            fromServer: false,
            expectedSessionId: $sessionId
        );

        $this->assertEquals($sessionId, $decrypted['session_id']);
    }

    public function test_decrypt_packet_rejects_session_id_mismatch(): void
    {
        [$packet, $authKey] = $this->craftValidPacket();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('session_id mismatch');
        PacketCodec::decryptPacket(
            $packet,
            $authKey,
            fromServer: false,
            expectedSessionId: 424242
        );
    }

    public function test_decrypt_packet_rejects_zero_message_id(): void
    {
        [$authKey, $packet] = $this->craftPacketWithHeaderOverrides(messageId: 0);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('message_id');
        PacketCodec::decryptPacket($packet, $authKey, fromServer: false);
    }

    public function test_decrypt_packet_rejects_message_id_too_far_in_future(): void
    {
        $futureId = ((time() + 301) << 32) | 4;
        [$authKey, $packet] = $this->craftPacketWithHeaderOverrides(messageId: $futureId);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('future');
        PacketCodec::decryptPacket($packet, $authKey, fromServer: false);
    }

    public function test_decrypt_packet_accepts_message_id_within_leeway(): void
    {
        $nearFutureId = ((time() + 100) << 32) | 4;
        [$authKey, $packet] = $this->craftPacketWithHeaderOverrides(messageId: $nearFutureId);

        $decrypted = PacketCodec::decryptPacket($packet, $authKey, fromServer: false);
        $this->assertEquals($nearFutureId, $decrypted['message_id']);
    }

    public function test_decrypt_packet_rejects_zero_payload_length(): void
    {
        [$authKey, $packet] = $this->craftPacketWithHeaderOverrides(dataLen: 0);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('payload length');
        PacketCodec::decryptPacket($packet, $authKey, fromServer: false);
    }

    public function test_decrypt_packet_rejects_truncating_payload_length(): void
    {
        [$authKey, $packet] = $this->craftPacketWithHeaderOverrides(dataLen: 4096);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('payload length');
        PacketCodec::decryptPacket($packet, $authKey, fromServer: false);
    }

    public function test_decrypt_packet_rejects_mismatched_msg_key(): void
    {
        [$authKey, $packet, $realMsgKey] = $this->craftPacketWithMismatchedMsgKey();

        // The ciphertext on this packet really is the plaintext we encrypted, and
        // it really is sealed under $realMsgKey — only the 16-byte msg_key header
        // field lies. So decryption itself succeeds and the msg_key verification
        // branch is the only thing that can reject it.
        $this->assertSame(16, strlen($realMsgKey));

        try {
            PacketCodec::decryptPacket($packet, $authKey, fromServer: false);
            $this->fail('decryptPacket accepted a packet whose msg_key header does not match its payload.');
        } catch (\RuntimeException $e) {
            $this->assertSame('MTProto packet integrity check failed: msg_key mismatch.', $e->getMessage());
        }
    }

    /**
     * Builds a client->server packet whose AES-IGE ciphertext is encrypted under
     * the REAL msg_key (so the decrypt succeeds and yields the true plaintext),
     * but whose 16-byte msg_key header field carries a value differing from it in
     * exactly one bit. Isolates the msg_key verification branch from every other
     * rejection path in decryptPacket().
     *
     * @return array{0: string, 1: string, 2: string} [authKey, packet, realMsgKey]
     */
    private function craftPacketWithMismatchedMsgKey(): array
    {
        $authKey = random_bytes(256);
        $payload = TLSerializer::packString('msg_key mismatch probe');
        $msgData = pack('P', 9876543210);          // server_salt
        $msgData .= pack('P', 1234567890);          // session_id
        $msgData .= pack('P', (time() << 32) | 4); // message_id
        $msgData .= pack('V', 2);                   // seq_no
        $msgData .= pack('V', strlen($payload));    // data_len
        $msgData .= $payload;

        $paddingLen = 16 - (strlen($msgData) % 16);
        if ($paddingLen < 12) {
            $paddingLen += 16;
        }
        $msgData .= random_bytes($paddingLen);

        $x = 0; // client -> server
        $realMsgKey = substr(hash('sha256', substr($authKey, 88 + $x, 32).$msgData, true), 8, 16);
        [$aesKey, $aesIv] = self::deriveKeysForTest($authKey, $realMsgKey, $x);
        $encrypted = AesIge::encrypt($msgData, $aesKey, $aesIv);

        $headerMsgKey = $realMsgKey;
        $headerMsgKey[15] = chr(ord($headerMsgKey[15]) ^ 0xFF); // one flipped bit
        $authKeyId = substr(hash('sha1', $authKey, true), 12, 8);

        return [$authKey, $authKeyId.$headerMsgKey.$encrypted, $realMsgKey];
    }

    /**
     * Hand-crafts a valid client->server packet so decryptPacket's
     * post-decrypt validations can be probed header-field by header-field.
     *
     * @return array{0: string, 1: string} [authKey, packet]
     */
    private function craftPacketWithHeaderOverrides(
        ?int $messageId = null,
        ?int $dataLen = null
    ): array {
        $authKey = random_bytes(256);
        $payload = TLSerializer::packString('probe payload for header validation');
        $msgData = pack('P', 9876543210);          // server_salt
        $msgData .= pack('P', 1234567890);          // session_id
        $msgData .= pack('P', $messageId ?? ((time() << 32) | 4));
        $msgData .= pack('V', 2);                   // seq_no
        $msgData .= pack('V', $dataLen ?? strlen($payload));
        $msgData .= $payload;

        $paddingLen = 16 - (strlen($msgData) % 16);
        if ($paddingLen < 12) {
            $paddingLen += 16;
        }
        $msgData .= random_bytes($paddingLen);

        $x = 0; // client -> server
        $msgKey = substr(hash('sha256', substr($authKey, 88 + $x, 32).$msgData, true), 8, 16);
        [$aesKey, $aesIv] = self::deriveKeysForTest($authKey, $msgKey, $x);
        $packet = substr(hash('sha1', $authKey, true), 12, 8).$msgKey.AesIge::encrypt($msgData, $aesKey, $aesIv);

        return [$authKey, $packet];
    }

    /** @return array{0: string, 1: string} [aesKey, aesIv] per MTProto 2.0 KDF */
    private static function deriveKeysForTest(string $authKey, string $msgKey, int $x): array
    {
        $sha256a = hash('sha256', $msgKey.substr($authKey, $x, 36), true);
        $sha256b = hash('sha256', substr($authKey, 40 + $x, 36).$msgKey, true);

        return [
            substr($sha256a, 0, 8).substr($sha256b, 8, 16).substr($sha256a, 24, 8),
            substr($sha256b, 0, 8).substr($sha256a, 8, 16).substr($sha256b, 24, 8),
        ];
    }

    /** @return array{0: string, 1: string, 2: int} [packet, authKey, sessionId] */
    private function craftValidPacket(): array
    {
        $authKey = random_bytes(256);
        $sessionId = 555000111;
        $payload = TLSerializer::packString('session echo validation');
        $packet = PacketCodec::encryptPacket(
            payload: $payload,
            authKey: $authKey,
            sessionId: $sessionId,
            serverSalt: 9876543210,
            seqNo: 1,
            toServer: true
        );

        return [$packet, $authKey, $sessionId];
    }

    // --- SRP input validation hardening (audit fix #3) ---

    public function test_password_calculator_rejects_srp_b_greater_or_equal_to_p(): void
    {
        $accountPassword = self::srpAccountPassword();
        $accountPassword['srp_B'] = self::dhPrime(); // == p

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('srp_B');
        PasswordCalculator::computeSrpProof($accountPassword, 'pw');
    }

    public function test_password_calculator_rejects_zero_srp_b(): void
    {
        $accountPassword = self::srpAccountPassword();
        $accountPassword['srp_B'] = str_repeat("\x00", 256); // B % p == 0

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('srp_B');
        PasswordCalculator::computeSrpProof($accountPassword, 'pw');
    }

    public function test_password_calculator_rejects_zero_u(): void
    {
        // SHA256(A|B) == 0 is infeasible to hit through computeSrpProof(), so the
        // u == 0 guard is validated directly on the public checker.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('u');
        PasswordCalculator::assertSrpScalarsValid(
            new BigInteger(str_repeat("\xBB", 256), 256),
            new BigInteger(self::dhPrime(), 256),
            new BigInteger(0)
        );
    }

    public function test_password_calculator_accepts_valid_srp_scalars(): void
    {
        PasswordCalculator::assertSrpScalarsValid(
            new BigInteger(str_repeat("\xBB", 256), 256), // 0 < B < p, B % p != 0
            new BigInteger(self::dhPrime(), 256),
            new BigInteger(1)
        );
        $this->assertTrue(true);
    }

    /** @return string The official 2048-bit MTProto SRP safe prime (raw bytes) */
    private static function dhPrime(): string
    {
        return hex2bin(AuthKeyFactory::KNOWN_DH_PRIME_HEX);
    }

    /** @return array<string, mixed> */
    private static function srpAccountPassword(): array
    {
        return [
            'has_password' => true,
            'current_algo' => [
                'g' => 3,
                'p' => self::dhPrime(),
                'salt1' => 'salt1_test_bytes',
                'salt2' => 'salt2_test_bytes',
            ],
            'srp_B' => str_repeat("\xBB", 256),
            'srp_id' => 123456789,
        ];
    }
}
