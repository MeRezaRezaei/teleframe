<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

/**
 * SpecCredentialFactory — the SPEC-FAITHFUL Telegram Passport fixture builder.
 *
 * It exists because the eight suites that exercise the login chain each
 * carried their own private `seal()` helper, and all eight of them built a
 * fixture the SPEC DOES NOT DESCRIBE: they hashed the PLAINTEXT, they applied
 * no padding, and they let OpenSSL add PKCS#7 to the ciphertext. A fixture
 * like that is not a weak test — it is a test that PINS THE BUG. It passes
 * only because the implementation makes the same three mistakes, which is
 * precisely how a spec violation shipped green through CI. One shared
 * factory, derived from `docs/reference/telegram-passport-official.md` and
 * not from the implementation, is the fix: a drift in either direction is now
 * a loud test failure instead of a silent agreement.
 *
 * Every line below is the reference's own wording, §3 and §4:
 *
 *   §3.1  secret = random bytes, sealed with RSA-**OAEP** to the service key.
 *   §3.2  key = SHA512(secret + hash)[0..32], iv = SHA512(secret + hash)[32..48]
 *         — concatenated input, RAW (non-hex) SHA-512.
 *   §3.3  "make sure that the credentials hash is equal to
 *         SHA256( credentials_data )" — credentials_data is the `data` field,
 *         so the hash is over the CIPHERTEXT, computed BEFORE decryption.
 *   §3.4  "padded with 32 to 255 random padding bytes to make its length
 *         divisible by 16 bytes. The first byte contains the length of this
 *         padding (including this byte)."
 *   §3    AES-256-CBC, and the payload is already a multiple of 16 — so NO
 *         PKCS#7 on top. The encrypt side therefore uses
 *         OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, which is what makes this
 *         fixture an actual test of the decryption mode.
 *
 * `sealMalformed*()` exist so a test can hand the implementation something
 * the spec forbids and prove it refuses — the padding byte and the tampered
 * hash cases are the ones a fixture built to fit the code cannot express.
 *
 * Zero regex; plain PHP; nothing is written to disk and no key is committed —
 * every keypair here is generated per test run.
 */
final class SpecCredentialFactory
{
    /** §3.4: the first byte is the padding length INCLUDING itself. */
    public const PADDING_MIN = 32;

    public const PADDING_MAX = 255;

    private string $privatePem;

    private string $publicPem;

    public function __construct(int $keyBits = 2048)
    {
        $key = openssl_pkey_new([
            'private_key_bits' => $keyBits,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        if ($key === false) {
            throw new \RuntimeException('openssl_pkey_new failed');
        }

        $private = '';
        if (! openssl_pkey_export($key, $private)) {
            throw new \RuntimeException('openssl_pkey_export failed');
        }

        $details = openssl_pkey_get_details($key);
        if (! is_array($details) || ! is_string($details['key'])) {
            throw new \RuntimeException('openssl_pkey_get_details failed');
        }

        $this->privatePem = $private;
        $this->publicPem = $details['key'];
    }

    public function privateKeyPem(): string
    {
        return $this->privatePem;
    }

    /**
     * Reuse a keypair a suite already generated (and already wrote to a temp
     * PEM file for `PassportKeyLoader`), so a test class keeps ONE key per
     * test and this factory seals against that same key.
     */
    public static function withKeys(string $privatePem, string $publicPem): self
    {
        $factory = new self;
        $factory->privatePem = $privatePem;
        $factory->publicPem = $publicPem;

        return $factory;
    }

    public function publicKeyPem(): string
    {
        return $this->publicPem;
    }

    /**
     * §3 — seal a `Credentials` payload into the Bot API envelope
     * {data, hash, secret}, all three base64.
     *
     * @param  array<string, mixed>  $payload  the `Credentials` JSON shape
     * @return array{data: string, hash: string, secret: string}
     */
    public function seal(array $payload): array
    {
        return $this->sealJson((string) json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{data: string, hash: string, secret: string}
     */
    public function sealJson(string $json): array
    {
        return $this->sealPadded($this->pad($json));
    }

    /**
     * Seal an ALREADY-PADDED plaintext — the seam a malformed-padding test
     * needs, since it has to bypass the padding this factory would otherwise
     * derive itself.
     *
     * @return array{data: string, hash: string, secret: string}
     */
    public function sealPadded(string $padded): array
    {
        $secret = random_bytes(32);
        $hash = $this->credentialsHash($padded);
        $data = $this->encrypt($padded, $secret, $hash);

        return [
            'data' => base64_encode($data),
            'hash' => base64_encode($hash),
            'secret' => base64_encode($this->sealSecret($secret)),
        ];
    }

    /**
     * §4 — `DataCredentials { data_hash, secret }` over a `SecureData.data`
     * field. RAW bytes both ways (§1: MTProto fields are not base64).
     *
     * @return array{hash: string, secret: string}
     */
    public function sealDataCredentials(string $json): array
    {
        $padded = $this->pad($json);
        $secret = random_bytes(32);
        $hash = $this->credentialsHash($padded);

        // The `data` field is returned so a test can assert the plaintext
        // really is the AES input of that hash, and that it is NOT the
        // ciphertext.
        return [
            'data' => $this->encrypt($padded, $secret, $hash),
            'hash' => $hash,
            'secret' => $this->sealSecret($secret),
        ];
    }

    /**
     * §4 — `FileCredentials { file_hash, secret }` over the DOWNLOADED
     * ciphertext bytes. Returns the downloaded bytes too, so a test can prove
     * the file round-trips.
     *
     * @return array{content: string, hash: string, secret: string}
     */
    public function sealFileCredentials(string $plainFile): array
    {
        $padded = $this->pad($plainFile);
        $secret = random_bytes(32);
        $hash = $this->credentialsHash($padded);

        return [
            'content' => $this->encrypt($padded, $secret, $hash),
            'hash' => $hash,
            'secret' => $this->sealSecret($secret),
        ];
    }

    /**
     * §3.4 — the padding, built from the spec's wording rather than from what
     * the implementation happens to accept.
     *
     * The length is 32..255 and includes the length byte, and the RESULT must
     * be divisible by 16 (that is the whole reason Telegram pads). When the
     * caller pins a length, the divisibility requirement is ASSERTED rather
     * than quietly adjusted: a fixture that cannot satisfy the spec is a
     * finding, not something to paper over with a different constant.
     */
    public function pad(string $json, ?int $paddingLength = null): string
    {
        $length = $paddingLength ?? self::PADDING_MIN + ((16 - ((strlen($json) + self::PADDING_MIN) % 16)) % 16);

        if ($length < self::PADDING_MIN || $length > self::PADDING_MAX) {
            throw new \RuntimeException('a padding length outside 32..255 is not a Telegram padding');
        }

        if ((strlen($json) + $length) % 16 !== 0) {
            throw new \RuntimeException(
                'this padding length does not make the payload divisible by 16 — the fixture, not the padding, is wrong'
            );
        }

        // First byte = the length INCLUDING ITSELF, then random bytes.
        return chr($length).random_bytes($length - 1).$json;
    }

    /**
     * A `Credentials` JSON string of EXACTLY $length bytes, so a test can pin
     * a specific padding length (255 needs a payload of length ≡ 1 mod 16).
     *
     * @throws \RuntimeException when $length is too small to carry the filler
     */
    public function credentialsJsonOfLength(int $length, string $nonce = 'abcdef0123456789'): string
    {
        $head = '{"nonce":"'.$nonce.'","filler":"';
        $tail = '"}';

        $fillerLength = $length - strlen($head) - strlen($tail);
        if ($fillerLength < 0) {
            throw new \RuntimeException('the requested json length is shorter than the envelope around the filler');
        }

        $json = $head.str_repeat('x', $fillerLength).$tail;
        if (strlen($json) !== $length) {
            throw new \RuntimeException('credentialsJsonOfLength produced the wrong length');
        }

        return $json;
    }

    /** §3.2 — SHA512(secret + hash): concatenation first, RAW (non-hex) SHA-512. */
    public function derive(string $secret, string $hash): string
    {
        return hash('sha512', $secret.$hash, true);
    }

    public function keyFor(string $secret, string $hash): string
    {
        return substr($this->derive($secret, $hash), 0, 32);
    }

    public function ivFor(string $secret, string $hash): string
    {
        return substr($this->derive($secret, $hash), 32, 16);
    }

    /**
     * §3.3 — "A hash of the padded credentials credentials_hash is calculated:
     * credentials_hash = SHA256( credentials )". `credentials` is the PADDED
     * payload, i.e. the AES INPUT. The hash is over the padded plaintext, NOT
     * over the ciphertext.
     *
     * This is also the only reading under which the algorithm can be
     * EXECUTED at all: the key is derived from this hash, so
     * "hash = SHA256(ciphertext)" asks for a fixed point — a ciphertext whose
     * own digest seeds the key that produces it — which no client can compute
     * and no fixture can construct. The order here (pad → hash → derive
     * key/iv → encrypt) is upstream's own order, transcribed literally.
     */
    public function credentialsHash(string $paddedPlaintext): string
    {
        return hash('sha256', $paddedPlaintext, true);
    }

    /** RSA-**OAEP** seal of a raw secret, under this factory's public key. */
    public function sealSecret(string $secret): string
    {
        $sealed = '';
        if (! openssl_public_encrypt($secret, $sealed, $this->publicPem, OPENSSL_PKCS1_OAEP_PADDING)) {
            throw new \RuntimeException('openssl_public_encrypt failed');
        }

        return $sealed;
    }

    /**
     * §3 — AES-256-CBC over the padded plaintext, with NO implicit block
     * padding: the payload is already a multiple of 16 by construction, so
     * upstream writes no PKCS#7 block. OPENSSL_ZERO_PADDING on this side is
     * what makes the fixture a real test of the decryption mode.
     */
    public function encrypt(string $padded, string $secret, string $hash): string
    {
        $data = openssl_encrypt(
            $padded,
            'aes-256-cbc',
            $this->keyFor($secret, $hash),
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $this->ivFor($secret, $hash)
        );
        if ($data === false) {
            throw new \RuntimeException('openssl_encrypt failed');
        }

        return $data;
    }
}
