<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\MTProto\Transport;

/**
 * One stateful AES-256-CTR pair for a single obfuscated2 TCP connection.
 *
 * Each direction owns its key/IV and a monotonic byte counter; the counter is
 * what guarantees the keystream is never reused across calls (OpenSSL resumes
 * a fresh counter per openssl_encrypt call, so an unmanaged instance would
 * XOR every chunk with the same keystream). Correct per direction:
 *
 *   - xcrypt($data, fromClient: true)  — data received FROM the client is
 *     decrypted with decrypt_key/decrypt_iv before it is relayed upstream;
 *     the client encrypted its 64-byte header through this direction first
 *     and kept only the encrypted tail, so the header already consumed the
 *     first 64 keystream bytes — construct via parseHeader()/fromHeader()
 *     (or seedHeaderBytes(64)) so payload traffic continues at offset 64.
 *   - xcrypt($data, fromClient: false) — upstream plaintext is encrypted with
 *     encrypt_key/encrypt_iv on its way back to the client (offset 0 — the
 *     server-side header the client decrypts its tail with is the from-client
 *     keystream, not this one).
 *
 * Own PSR-4 file (not a sibling in Obfuscated2Codec.php) because composer's
 * optimized classmap only autoloads the class whose name matches the file.
 */
final class Obfuscated2Cipher
{
    /** @var positive-int */
    private const CTR_IV_LENGTH = 16;

    private int $fromClientFed = 0;

    private int $toClientFed = 0;

    public function __construct(
        private readonly string $decryptKey,
        private readonly string $encryptKey,
        private readonly string $decryptIv,
        private readonly string $encryptIv,
    ) {}

    /**
     * @param  array{dc_id: int, protocol: string, decryptKey: string, encryptKey: string, decryptIv: string, encryptIv: string}  $parsed
     */
    public static function fromParsedHeader(array $parsed): self
    {
        $cipher = new self(
            $parsed['decryptKey'],
            $parsed['encryptKey'],
            $parsed['decryptIv'],
            $parsed['encryptIv'],
        );
        $cipher->fromClientFed = Obfuscated2Codec::HEADER_SIZE;

        return $cipher;
    }

    public static function fromHeader(string $header): self
    {
        return self::fromParsedHeader(Obfuscated2Codec::parseHeader($header));
    }

    /**
     * Transforms $data in whichever direction is selected, advancing the
     * per-direction counter by exactly strlen($data), so byte streams split
     * at arbitrary chunk boundaries stay locked to both peers.
     */
    public function xcrypt(string $data, bool $fromClient): string
    {
        $length = strlen($data);
        if ($length === 0) {
            return '';
        }

        if ($fromClient) {
            $out = self::xcryptAt($data, $this->decryptKey, $this->decryptIv, $this->fromClientFed);
            $this->fromClientFed += $length;
        } else {
            $out = self::xcryptAt($data, $this->encryptKey, $this->encryptIv, $this->toClientFed);
            $this->toClientFed += $length;
        }

        return $out;
    }

    /**
     * XORs $data with the AES-256-CTR keystream at stream offset $offset.
     *
     * The keystream is the AES-CTR output on a zero block stream seeded at
     * counter = $iv + ($offset >> 4), skipping ($offset & 15) leading bytes so
     * an arbitrary mid-stream byte count can be processed without ever
     * re-issuing a counter already consumed. Straightforward string functions
     * only — zero regex, per the src/Core discipline.
     */
    public static function xcryptAt(string $data, string $key, string $iv, int $offset): string
    {
        $length = strlen($data);
        $skip = $offset & 0x0F;
        $counter = self::addToCounter($iv, $offset >> 4);
        $keystream = openssl_encrypt(
            str_repeat("\0", $length + $skip),
            'aes-256-ctr',
            $key,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $counter,
        );

        return $data ^ substr($keystream, $skip, $length);
    }

    /**
     * Big-endian 128-bit counter arithmetic: adds $blocks to the 16-byte
     * counter $iv (AES-256-CTR resumes from the counter value, not the IV).
     */
    private static function addToCounter(string $iv, int $blocks): string
    {
        if ($blocks === 0) {
            return $iv;
        }

        $counter = $iv;
        $carry = $blocks;
        for ($i = self::CTR_IV_LENGTH - 1; $i >= 0; $i--) {
            $sum = ord($counter[$i]) + ($carry & 0xFF);
            $counter[$i] = chr($sum & 0xFF);
            $carry = ($carry >> 8) + ($sum >> 8);
            if ($carry === 0) {
                break;
            }
        }

        return $counter;
    }
}
