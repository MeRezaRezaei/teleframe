<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Tests\Wire;

use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\AesIge;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\AuthKeyFactory;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLDecoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\Formats\Keys\PKCS1;
use phpseclib3\Math\BigInteger;
use RuntimeException;

/**
 * In-process fake Telegram DC for offline AuthKeyFactory::generate() coverage:
 * speaks the whole handshake (req_pq_multi / req_DH_params / set_client_DH_params)
 * under a locally generated 2048-bit RSA key, performing a genuine RSA-PAD unwrap
 * of req_DH_params and re-encrypting server_DH_inner_data / dh_gen_ok with the
 * tmp-AES keys derived from the recovered new_nonce. Tamper switches let tests
 * forge spec-violating responses for the negative paths.
 *
 * Own file so tests/Core/MTProto runs standalone (it is shared by
 * AuthKeyFactoryTest) without dragging in the offline_test class.
 */
final class FakeHandshakeServer
{
    public bool $tamperDhParamsNonce = false;

    public bool $tamperDhParamsServerNonce = false;

    public bool $tamperInnerNonce = false;

    public bool $tamperDhPrime = false;

    public ?int $overrideG = null;

    public bool $tamperGenNonce = false;

    public bool $tamperGenServerNonce = false;

    public bool $tamperGenHash1 = false;

    public string $serverAuthKey = '';

    /** @var array{pem: string, n: BigInteger, e: BigInteger, d: BigInteger}|null */
    private static ?array $key = null;

    private string $nonce = '';

    private string $serverNonce = '';

    private string $newNonce = '';

    private BigInteger $gaSecret;

    public static function keyPem(): string
    {
        if (self::$key === null) {
            $private = RSA::createKey(2048);
            $pem = $private->getPublicKey()->toString('PKCS1'); // client side only ever sees the public key
            $body = '';
            foreach (explode("\n", $private->toString('PKCS1')) as $line) {
                $line = trim($line);
                if ($line !== '' && ! str_starts_with($line, '-----')) {
                    $body .= $line;
                }
            }
            $components = PKCS1::load((string) base64_decode($body, true));
            self::$key = [
                'pem' => $pem,
                'n' => $components['modulus'],
                'e' => $components['publicExponent'],
                'd' => $components['privateExponent'],
            ];
        }

        return self::$key['pem'];
    }

    public function respond(string $request): string
    {
        $offset = 0;
        $req = TLDecoder::decodeObject($request, $offset);

        return match ($req['_']) {
            'req_pq_multi' => $this->resPq((string) $req['nonce']),
            'req_DH_params' => $this->serverDhParams($req),
            'set_client_DH_params' => $this->dhGenResult($req),
            default => throw new RuntimeException('fake server: unexpected constructor '.$req['_']),
        };
    }

    private function resPq(string $nonce): string
    {
        $this->nonce = $nonce;
        $this->serverNonce = random_bytes(16);
        // pq = 101 * 65537 (small semiprime, instantly factorized by PqFactorizer)
        $pq = (new BigInteger(101))->multiply(new BigInteger(65537))->toBytes();
        // wire fingerprint: the raw SHA1[12:20] bytes read as a little-endian
        // long — exactly what generate()'s candidate loop packs and compares
        $wireFingerprint = unpack('P', AuthKeyFactory::fingerprintBytesOf(self::keyPem()))[1];

        return TLEncoder::encodeObject('resPQ', [
            'nonce' => $nonce,
            'server_nonce' => $this->serverNonce,
            'pq' => $pq,
            'server_public_key_fingerprints' => [$wireFingerprint],
        ]);
    }

    /** @param array<string, mixed> $req */
    private function serverDhParams(array $req): string
    {
        self::keyPem();
        $key = self::$key ?? throw new RuntimeException('fake server: key not booted');

        // RSA-PAD unwrap (reverse of AuthKeyFactory::rsaPadEncrypt) to recover new_nonce
        $payload = str_pad(
            (new BigInteger((string) $req['encrypted_data'], 256))->powMod($key['d'], $key['n'])->toBytes(),
            256,
            "\x00",
            STR_PAD_LEFT
        );
        $tempKey = substr($payload, 0, 32) ^ hash('sha256', substr($payload, 32), true);
        $dataWithHash = AesIge::decrypt(substr($payload, 32), $tempKey, str_repeat("\x00", 32));
        $dataWithPadding = strrev(substr($dataWithHash, 0, 192));
        if (! hash_equals(substr($dataWithHash, 192), hash('sha256', $tempKey.$dataWithPadding, true))) {
            throw new RuntimeException('fake server: RSA-PAD hash mismatch');
        }
        $offset = 0;
        $inner = TLDecoder::decodeObject($dataWithPadding, $offset);
        if ($inner['_'] !== 'p_q_inner_data_dc'
            || $inner['nonce'] !== $this->nonce
            || $inner['server_nonce'] !== $this->serverNonce) {
            throw new RuntimeException('fake server: bad p_q_inner_data_dc');
        }
        $this->newNonce = (string) $inner['new_nonce'];

        $g = new BigInteger((string) ($this->overrideG ?? 3));
        $primeBytes = hex2bin(AuthKeyFactory::KNOWN_DH_PRIME_HEX);
        if ($this->tamperDhPrime && $primeBytes !== false) {
            $primeBytes[200] = chr(ord($primeBytes[200]) ^ 0x01);
        }
        $prime = new BigInteger(bin2hex((string) $primeBytes), 16);
        $this->gaSecret = new BigInteger(random_bytes(256), 256);
        $gA = str_pad($g->modPow($this->gaSecret, $prime)->toBytes(), 256, "\x00", STR_PAD_LEFT);

        $innerData = TLEncoder::encodeObject('server_DH_inner_data', [
            'nonce' => $this->tamperInnerNonce ? self::flip($this->nonce) : $this->nonce,
            'server_nonce' => $this->serverNonce,
            'g' => (int) $g->toString(),
            'dh_prime' => (string) $primeBytes,
            'g_a' => $gA,
            'server_time' => time(),
        ]);
        $padded = sha1($innerData, true).$innerData;
        $padLen = (16 - (strlen($padded) % 16)) % 16;
        $padded .= $padLen > 0 ? random_bytes($padLen) : '';
        $encryptedAnswer = AesIge::encrypt(
            $padded,
            AuthKeyFactory::tmpAesKey($this->newNonce, $this->serverNonce),
            AuthKeyFactory::tmpAesIv($this->newNonce, $this->serverNonce)
        );

        return TLEncoder::encodeObject('server_DH_params_ok', [
            'nonce' => $this->tamperDhParamsNonce ? self::flip($this->nonce) : $this->nonce,
            'server_nonce' => $this->tamperDhParamsServerNonce ? self::flip($this->serverNonce) : $this->serverNonce,
            'encrypted_answer' => $encryptedAnswer,
        ]);
    }

    /** @param array<string, mixed> $req */
    private function dhGenResult(array $req): string
    {
        $plain = AesIge::decrypt(
            (string) $req['encrypted_data'],
            AuthKeyFactory::tmpAesKey($this->newNonce, $this->serverNonce),
            AuthKeyFactory::tmpAesIv($this->newNonce, $this->serverNonce)
        );
        $clientInner = AuthKeyFactory::decodeHashPrefixed($plain);
        if ($clientInner['_'] !== 'client_DH_inner_data') {
            throw new RuntimeException('fake server: bad client_DH_inner_data');
        }
        $prime = new BigInteger(AuthKeyFactory::KNOWN_DH_PRIME_HEX, 16);
        $gB = new BigInteger(bin2hex((string) $clientInner['g_b']), 16);
        $this->serverAuthKey = str_pad($gB->modPow($this->gaSecret, $prime)->toBytes(), 256, "\x00", STR_PAD_LEFT);

        $hash1 = AuthKeyFactory::newNonceHash1($this->newNonce, $this->serverAuthKey);

        return TLEncoder::encodeObject('dh_gen_ok', [
            'nonce' => $this->tamperGenNonce ? self::flip($this->nonce) : $this->nonce,
            'server_nonce' => $this->tamperGenServerNonce ? self::flip($this->serverNonce) : $this->serverNonce,
            'new_nonce_hash1' => $this->tamperGenHash1 ? self::flip($hash1) : $hash1,
        ]);
    }

    private static function flip(string $bytes): string
    {
        $bytes[0] = chr(ord($bytes[0]) ^ 0x01);

        return $bytes;
    }
}
