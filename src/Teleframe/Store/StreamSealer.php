<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store;

use InvalidArgumentException;
use LogicException;
use RuntimeException;
use SodiumException;

/**
 * Streaming seal/open for one chunk-document (verbatim S2/S8).
 *
 * Backup's VaultCrypto seals whole in-memory chunks (fine at 4 MiB, fatal
 * at 4GiB). StreamSealer keeps ONE XChaCha20-Poly1305 secretstream per
 * chunk-document and pushes/pulls part-by-part: the first uploaded part
 * carries the 24-byte secretstream header prefix, every push is tagged
 * MESSAGE, and the final push is tagged FINAL — so truncation, reorder
 * and tamper detection stay structural at any size while peak memory
 * stays at one part (512 KiB).
 *
 * Wire shape per chunk-document (concatenated across its parts):
 *   header(24) || push(part0, MESSAGE) || push(part1, MESSAGE) ...
 *   || push(last, FINAL)
 * An empty chunk seals as header(24) || push('', FINAL).
 */
final class StreamSealer
{
    private const KEY_BYTES = 32;

    /** @var string|null */
    private $pushState;

    /** @var string|null */
    private $pullState;

    /** @var bool */
    private $pushFinalized = false;

    /** @var bool */
    private $pullFinalized = false;

    /**
     * Start sealing. Returns the 24-byte secretstream header, which the
     * caller must persist as the first 24 bytes of the chunk's wire
     * stream (sendHeader() then push() the parts).
     */
    public function startSeal(string $key): string
    {
        self::assertKey($key);

        try {
            [$this->pushState, $header] = sodium_crypto_secretstream_xchacha20poly1305_init_push($key);
        } catch (SodiumException $e) {
            throw new RuntimeException('secretstream init failed: '.$e->getMessage(), 0, $e);
        }

        $this->pushFinalized = false;

        return $header;
    }

    /**
     * Seal the next plaintext slice. $final must be true only for the
     * last slice of the chunk-document (an empty final slice is legal —
     * it seals an empty chunk as header || push('', FINAL)).
     */
    public function push(string $key, string $plaintext, bool $final): string
    {
        self::assertKey($key);

        if ($this->pushState === null) {
            throw new LogicException('startSeal() must be called before push()');
        }
        if ($this->pushFinalized) {
            throw new LogicException('chunk stream already sealed FINAL');
        }

        $tag = $final
            ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL
            : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE;

        try {
            $cipher = sodium_crypto_secretstream_xchacha20poly1305_push($this->pushState, $plaintext, '', $tag);
        } catch (SodiumException $e) {
            throw new RuntimeException('secretstream push failed: '.$e->getMessage(), 0, $e);
        }

        if ($final) {
            $this->pushFinalized = true;
        }

        return $cipher;
    }

    /**
     * Feed the next ciphertext slice (as received from the wire, in
     * order). The first slice must include the 24-byte header at its
     * start (feed it verbatim; this class peels it off). Returns the
     * plaintext slice; throws on tamper/truncation/wrong key.
     */
    public function pull(string $key, string $ciphertext, bool $finalExpected): string
    {
        self::assertKey($key);

        if ($this->pullFinalized) {
            throw new LogicException('chunk stream already pulled FINAL');
        }

        try {
            if ($this->pullState === null) {
                if (strlen($ciphertext) <= SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES) {
                    // Could be the header + body in one slice, or a truncated stream.
                    if (strlen($ciphertext) < SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES) {
                        throw new RuntimeException('Decryption failed: truncated header');
                    }
                }
                $header = substr($ciphertext, 0, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
                $this->pullState = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, $key);
                $body = substr($ciphertext, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
            } else {
                $body = $ciphertext;
            }
        } catch (SodiumException $e) {
            throw new RuntimeException('Decryption failed: '.$e->getMessage(), 0, $e);
        }

        if ($body === '' && ! $finalExpected) {
            // Header-only slice: legal intermediate (caller split the wire oddly).
            return '';
        }

        try {
            $result = sodium_crypto_secretstream_xchacha20poly1305_pull($this->pullState, $body);
        } catch (SodiumException $e) {
            throw new RuntimeException('Decryption failed: '.$e->getMessage(), 0, $e);
        }

        if ($result === false) {
            throw new RuntimeException('Decryption failed: corrupted chunk stream');
        }

        [$plain, $tag] = $result;

        if ($finalExpected) {
            if ($tag !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL) {
                throw new RuntimeException('Decryption failed: chunk stream is not FINAL');
            }
            $this->pullFinalized = true;
        } elseif ($tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL) {
            // Producer sealed FINAL early — treat as corrupt; the manifest said more was coming.
            throw new RuntimeException('Decryption failed: premature FINAL tag');
        }

        return $plain;
    }

    public function sealed(): bool
    {
        return $this->pushFinalized;
    }

    public function pulled(): bool
    {
        return $this->pullFinalized;
    }

    private static function assertKey(string $key): void
    {
        if (strlen($key) !== self::KEY_BYTES) {
            throw new InvalidArgumentException(
                'key must be exactly '.self::KEY_BYTES.' raw bytes, got '.strlen($key),
            );
        }
    }
}
