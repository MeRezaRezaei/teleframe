<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\MTProto\Crypto;

use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLDecoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLSerializer;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA\Formats\Keys\PKCS1;
use phpseclib3\Math\BigInteger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * SERVER half of the MTProto 2.0 authorization-key exchange — plays
 * "Telegram" to a browser-side client so the /apiws terminal seats a relay
 * auth key while the vault credential stays server-side.
 *
 * Flow (mirror of AuthKeyFactory::generate, inverted):
 *   req_pq_multi        -> resPQ
 *   req_DH_params        -> server_DH_params_ok   (raw-RSA unwrap via the
 *                            relay-local private key, replay-reversed)
 *   set_client_DH_params -> dh_gen_ok             (state becomes 'seated')
 *
 * The RSA private key is a relay-local identity that authenticates the
 * handshake to our terminal only; it is never a Telegram credential.
 *
 * Requests and responses use the TLDecoder/TLEncoder array shape (string
 * keys including '_' for the constructor name), wrapped as php objects:
 * pass the request as `(object) $decodedArray`; the returned response is
 * `(object) $responseArray` and casts straight back to the array form
 * TLEncoder::encodeObject accepts.
 *
 * Zero-regex: string functions / unpack only (phpstan disallowed-calls gate).
 */
final class ServerDhHandshake
{
    /** Minimum relay RSA modulus size (W-08) — below is trivially factorable. */
    public const MIN_RELAY_RSA_BITS = 2048;

    private const STATE_AWAITING_DH_PARAMS = 'awaiting_dh_params';

    private const STATE_AWAITING_CLIENT_DH = 'awaiting_client_dh';

    private const STATE_SEATED = 'seated';

    private const DH_G = 3;

    /** @var array{modulus: BigInteger, publicExponent: BigInteger, privateExponent: BigInteger}|null */
    private ?array $rsa = null;

    private string $state = self::STATE_AWAITING_DH_PARAMS;

    private string $nonce = '';

    private string $serverNonce = '';

    private string $newNonce = '';

    private string $pq = '';

    private ?BigInteger $dhSecret = null;

    private string $authKey = '';

    private LoggerInterface $logger;

    public function __construct(private string $rsaPrivateKeyPem, ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger;
    }

    /**
     * One step of the exchange. The request object carries the decoded TL
     * constructor ('_' => 'req_pq_multi' | 'req_DH_params' |
     * 'set_client_DH_params') plus its fields.
     *
     * @throws RuntimeException on out-of-order or non-matching requests
     */
    public function handle(object $request): object
    {
        $req = (array) $request;
        $step = (string) ($req['_'] ?? '');

        try {
            return match ($step) {
                'req_pq_multi' => $this->handleReqPqMulti($req),
                'req_DH_params' => $this->handleReqDhParams($req),
                'set_client_DH_params' => $this->handleSetClientDhParams($req),
                default => throw new RuntimeException("ServerDhHandshake: unexpected request constructor '{$step}'"),
            };
        } catch (RuntimeException $e) {
            $this->logger->error('teleframe.mtproto.serverdh.handshake.failed', [
                'step' => $step,
                'state' => $this->state,
                'exception' => $e::class,
            ]);
            throw $e;
        }
    }

    /** 256 raw bytes of the seated auth key; null until dh_gen_ok. */
    public function authKey(): ?string
    {
        return $this->authKey === '' ? null : $this->authKey;
    }

    /** The client nonce echoed in resPQ; null until the first step. */
    public function browserNonce(): ?string
    {
        return $this->nonce === '' ? null : $this->nonce;
    }

    /** 'awaiting_dh_params' | 'awaiting_client_dh' | 'seated'. */
    public function state(): string
    {
        return $this->state;
    }

    /** @param array<string, mixed> $req */
    private function handleReqPqMulti(array $req): object
    {
        if ($this->state !== self::STATE_AWAITING_DH_PARAMS) {
            throw new RuntimeException('ServerDhHandshake: req_pq_multi out of order (state='.$this->state.')');
        }

        $nonce = (string) ($req['nonce'] ?? '');
        if (strlen($nonce) !== 16) {
            throw new RuntimeException('ServerDhHandshake: req_pq_multi nonce must be 16 bytes');
        }

        $this->nonce = $nonce;
        $this->serverNonce = random_bytes(16);
        $this->pq = $this->freshPq();
        $this->state = self::STATE_AWAITING_CLIENT_DH;

        return (object) [
            '_' => 'resPQ',
            'nonce' => $this->nonce,
            'server_nonce' => $this->serverNonce,
            'pq' => $this->pq,
            'server_public_key_fingerprints' => [$this->fingerprintWireValue()],
        ];
    }

    /**
     * A fresh random semiprime for THIS handshake only: two random primes in
     * [2^31, 2^32) — product ~62-64 bits, the fattable window the MTProto
     * spec reserves for pq (spec: p,q random primes in [2^31, 2^63), pq
     * issued fresh per req_pq_multi). Issuing a per-session pq forces the
     * client to actually factor it inside req_DH_params instead of caching
     * p/q for a constant: a replayed or skipped factorization is caught by
     * the p*q === pq check on the next step. No secret must be retained —
     * the client returns p/q and the product is verified by multiplication,
     * exactly as the official server treats a req_DH_params.
     */
    private function freshPq(): string
    {
        $p = BigInteger::randomPrime(32);
        $q = BigInteger::randomPrime(32);

        return $p->multiply($q)->toBytes();
    }

    /** @param array<string, mixed> $req */
    private function handleReqDhParams(array $req): object
    {
        if ($this->state !== self::STATE_AWAITING_CLIENT_DH) {
            throw new RuntimeException('ServerDhHandshake: req_DH_params out of order (state='.$this->state.')');
        }

        $this->assertNonceEcho($req, 'req_DH_params');
        if ((int) ($req['public_key_fingerprint'] ?? -1) !== $this->fingerprintWireValue()) {
            throw new RuntimeException('ServerDhHandshake: req_DH_params public_key_fingerprint mismatch');
        }

        $rsa = $this->rsa();

        // Reversed RSA-PAD (AuthKeyFactory::rsaPadEncrypt): raw RSA with the
        // private exponent, then temp-key unmix, AES-IGE decrypt, SHA256
        // integrity check, un-reverse of the padding.
        $radix = new BigInteger((string) ($req['encrypted_data'] ?? ''), 256);
        $payload = str_pad($radix->powMod($rsa['privateExponent'], $rsa['modulus'])->toBytes(), 256, "\x00", STR_PAD_LEFT);
        $tempKey = substr($payload, 0, 32) ^ hash('sha256', substr($payload, 32), true);
        $dataWithHash = AesIge::decrypt(substr($payload, 32), $tempKey, str_repeat("\x00", 32));
        $dataWithPadding = strrev(substr($dataWithHash, 0, 192));
        if (! hash_equals(substr($dataWithHash, 192), hash('sha256', $tempKey.$dataWithPadding, true))) {
            throw new RuntimeException('ServerDhHandshake: req_DH_params RSA-PAD integrity mismatch');
        }

        $offset = 0;
        $inner = TLDecoder::decodeObject($dataWithPadding, $offset);
        if (! in_array($inner['_'], ['p_q_inner_data_dc', 'p_q_inner_data', 'p_q_inner_data_temp_dc'], true)) {
            throw new RuntimeException('ServerDhHandshake: req_DH_params inner data not a p_q_inner_data family');
        }
        $this->assertNonceEcho((array) $inner, 'req_DH_params inner data');
        $this->newNonce = (string) ($inner['new_nonce'] ?? '');
        if (strlen($this->newNonce) !== 32) {
            throw new RuntimeException('ServerDhHandshake: req_DH_params new_nonce must be 32 bytes');
        }

        // p/q must factor back into the pq we handed out in resPQ.
        $p = new BigInteger(bin2hex((string) ($req['p'] ?? '')), 16);
        $q = new BigInteger(bin2hex((string) ($req['q'] ?? '')), 16);
        if (! $p->multiply($q)->equals(new BigInteger(bin2hex($this->pq), 16))) {
            throw new RuntimeException('ServerDhHandshake: req_DH_params p*q does not match pq');
        }

        $g = new BigInteger((string) self::DH_G);
        $prime = new BigInteger(AuthKeyFactory::KNOWN_DH_PRIME_HEX, 16);
        $secret = new BigInteger(random_bytes(256), 256);
        $this->dhSecret = $secret;
        $gA = str_pad($g->modPow($secret, $prime)->toBytes(), 256, "\x00", STR_PAD_LEFT);

        $innerData = TLEncoder::encodeObject('server_DH_inner_data', [
            'nonce' => $this->nonce,
            'server_nonce' => $this->serverNonce,
            'g' => self::DH_G,
            'dh_prime' => hex2bin(AuthKeyFactory::KNOWN_DH_PRIME_HEX),
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

        return (object) [
            '_' => 'server_DH_params_ok',
            'nonce' => $this->nonce,
            'server_nonce' => $this->serverNonce,
            'encrypted_answer' => $encryptedAnswer,
        ];
    }

    /** @param array<string, mixed> $req */
    private function handleSetClientDhParams(array $req): object
    {
        if ($this->state !== self::STATE_AWAITING_CLIENT_DH) {
            throw new RuntimeException('ServerDhHandshake: set_client_DH_params out of order (state='.$this->state.')');
        }
        if ($this->dhSecret === null) {
            throw new RuntimeException('ServerDhHandshake: set_client_DH_params before req_DH_params');
        }

        $this->assertNonceEcho($req, 'set_client_DH_params');

        $plain = AesIge::decrypt(
            (string) ($req['encrypted_data'] ?? ''),
            AuthKeyFactory::tmpAesKey($this->newNonce, $this->serverNonce),
            AuthKeyFactory::tmpAesIv($this->newNonce, $this->serverNonce)
        );
        $clientInner = AuthKeyFactory::decodeHashPrefixed($plain);
        if ($clientInner['_'] !== 'client_DH_inner_data') {
            throw new RuntimeException('ServerDhHandshake: set_client_DH_params payload not client_DH_inner_data');
        }
        $this->assertNonceEcho((array) $clientInner, 'client_DH_inner_data');

        $prime = new BigInteger(AuthKeyFactory::KNOWN_DH_PRIME_HEX, 16);
        $gB = new BigInteger(bin2hex((string) ($clientInner['g_b'] ?? '')), 16);
        $one = new BigInteger(1);

        if ($gB->compare($one) <= 0 || $gB->compare($prime->subtract($one)) >= 0) {
            throw new RuntimeException('ServerDhHandshake: client g_b out of range');
        }

        $authKey = str_pad($gB->modPow($this->dhSecret, $prime)->toBytes(), 256, "\x00", STR_PAD_LEFT);
        $this->authKey = $authKey;
        $this->state = self::STATE_SEATED;

        $this->logger->debug('teleframe.mtproto.serverdh.seated', [
            'key_length' => 256,
        ]);

        return (object) [
            '_' => 'dh_gen_ok',
            'nonce' => $this->nonce,
            'server_nonce' => $this->serverNonce,
            'new_nonce_hash1' => AuthKeyFactory::newNonceHash1($this->newNonce, $authKey),
        ];
    }

    /**
     * rsa_public_key n:string e:string fingerprint — final 8 bytes of
     * SHA1(packString(n) || packString(e)), the value the client's fingerprint
     * match walks against (see AuthKeyFactory::fingerprintBytesOf).
     */
    private function fingerprintWireValue(): int
    {
        $rsa = $this->rsa();
        $bytes = substr(sha1(
            TLSerializer::packString($rsa['modulus']->toBytes())
                .TLSerializer::packString($rsa['publicExponent']->toBytes()),
            true
        ), 12, 8);
        $unpacked = unpack('P', $bytes);
        if (! is_array($unpacked) || ! isset($unpacked[1])) {
            throw new RuntimeException('ServerDhHandshake: fingerprint unpack failed');
        }

        return (int) $unpacked[1];
    }

    /**
     * Loads the relay-local RSA private key once: PKCS#1 DER -> components
     * (modulus / public exponent / private exponent) for the raw RSA unwrap.
     *
     * The CA 521-bit generation-D roundtrip is rejected here (W-08): a
     * modulus under 2048 bits is trivially factorable and must never seat a
     * terminal key. Keys smaller than the floor are refused at load time,
     * not at first client handshake.
     *
     * @return array{modulus: BigInteger, publicExponent: BigInteger, privateExponent: BigInteger}
     */
    private function rsa(): array
    {
        if ($this->rsa === null) {
            $key = PublicKeyLoader::load($this->rsaPrivateKeyPem);
            $pkcs1 = $key->toString('PKCS1');
            $body = '';
            foreach (explode("\n", $pkcs1) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '-----')) {
                    continue;
                }
                $body .= $line;
            }
            $components = PKCS1::load((string) base64_decode($body, true));
            if (! isset($components['modulus'], $components['publicExponent'], $components['privateExponent'])) {
                throw new RuntimeException('ServerDhHandshake: RSA private key components could not be extracted');
            }
            if ($components['modulus']->getLength() < self::MIN_RELAY_RSA_BITS) {
                throw new RuntimeException('ServerDhHandshake: relay RSA private key is only '
                    .$components['modulus']->getLength()
                    .' bits; at least '.self::MIN_RELAY_RSA_BITS.' bits required');
            }
            $this->rsa = [
                'modulus' => $components['modulus'],
                'publicExponent' => $components['publicExponent'],
                'privateExponent' => $components['privateExponent'],
            ];
        }

        return $this->rsa;
    }

    /** @param array<string, mixed> $obj */
    private function assertNonceEcho(array $obj, string $context): void
    {
        if (! hash_equals($this->nonce, (string) ($obj['nonce'] ?? ''))) {
            throw new RuntimeException('ServerDhHandshake: '.$context.' nonce mismatch');
        }
        if (! hash_equals($this->serverNonce, (string) ($obj['server_nonce'] ?? ''))) {
            throw new RuntimeException('ServerDhHandshake: '.$context.' server_nonce mismatch');
        }
    }
}
