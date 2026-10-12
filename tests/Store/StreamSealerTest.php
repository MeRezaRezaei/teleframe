<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use InvalidArgumentException;
use LogicException;
use MeRezaRezaei\Teleframe\Store\StreamSealer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * StreamSealer (verbatim S2/S8): one XChaCha20-Poly1305 secretstream per
 * chunk-document, pushed/pulled part-by-part so peak memory stays at one
 * 512 KiB part while truncation/reorder/tamper detection stays structural.
 * Covers seal→pull roundtrips, the header prefix, FINAL enforcement,
 * wrong-key and truncated-stream failures, and empty-chunk sealing.
 */
final class StreamSealerTest extends TestCase
{
    private function key(string $seed = 'a'): string
    {
        return str_repeat($seed, 32);
    }

    public function test_seal_pull_roundtrips_multi_part_chunk(): void
    {
        $key = $this->key('a');
        $sealer = new StreamSealer;

        $header = $sealer->startSeal($key);
        $parts = [
            $sealer->push($key, 'first-part', false),
            $sealer->push($key, 'second-part', false),
            $sealer->push($key, 'last!', true),
        ];
        $this->assertTrue($sealer->sealed());

        $opener = new StreamSealer;
        $opened = $opener->pull($key, $header.$parts[0], false);
        $this->assertSame('first-part', $opened);
        $opened = $opener->pull($key, $parts[1], false);
        $this->assertSame('second-part', $opened);
        $opened = $opener->pull($key, $parts[2], true);
        $this->assertSame('last!', $opened);
        $this->assertTrue($opener->pulled());
    }

    public function test_seal_pull_roundtrips_large_payload(): void
    {
        $key = $this->key('b');
        $plain = str_repeat('0123456789abcdef', 65536); // 1 MiB
        $sealer = new StreamSealer;
        $header = $sealer->startSeal($key);
        $cipher = $header.$sealer->push($key, $plain, true);

        $opener = new StreamSealer;
        $this->assertSame($plain, $opener->pull($key, $cipher, true));
    }

    public function test_wrong_key_fails_open_loudly(): void
    {
        $sealer = new StreamSealer;
        $header = $sealer->startSeal($this->key('a'));
        $cipher = $header.$sealer->push($this->key('a'), 'secret', true);

        $opener = new StreamSealer;

        $this->expectException(RuntimeException::class);
        $opener->pull($this->key('c'), $cipher, true);
    }

    public function test_truncated_stream_fails_on_missing_final(): void
    {
        $sealer = new StreamSealer;
        $header = $sealer->startSeal($this->key('a'));
        $cipher = $header.$sealer->push($this->key('a'), 'partial', false); // never FINAL

        $opener = new StreamSealer;

        $this->expectException(RuntimeException::class);
        $opener->pull($this->key('a'), $cipher, true);
    }

    public function test_caller_must_end_each_slice_correctly(): void
    {
        $sealer = new StreamSealer;
        $header = $sealer->startSeal($this->key('a'));
        $cipher = $header.$sealer->push($this->key('a'), 'x', true);

        $opener = new StreamSealer;
        // Caller expects a message but the producer sealed FINAL early.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('premature FINAL');
        $opener->pull($this->key('a'), $cipher, false);
    }

    public function test_push_after_final_is_rejected(): void
    {
        $sealer = new StreamSealer;
        $sealer->startSeal($this->key('a'));
        $sealer->push($this->key('a'), 'x', true);

        $this->expectException(LogicException::class);
        $sealer->push($this->key('a'), 'y', false);
    }

    public function test_push_without_start_is_rejected(): void
    {
        $sealer = new StreamSealer;

        $this->expectException(LogicException::class);
        $sealer->push($this->key('a'), 'x', true);
    }

    public function test_pull_after_final_is_rejected(): void
    {
        $sealer = new StreamSealer;
        $header = $sealer->startSeal($this->key('a'));
        $cipher = $header.$sealer->push($this->key('a'), 'x', true);

        $opener = new StreamSealer;
        $opener->pull($this->key('a'), $cipher, true);

        $this->expectException(LogicException::class);
        $opener->pull($this->key('a'), 'more', true);
    }

    public function test_wrong_length_key_is_rejected(): void
    {
        $sealer = new StreamSealer;

        $this->expectException(InvalidArgumentException::class);
        $sealer->startSeal('too-short');
    }

    public function test_empty_chunk_seals_as_header_plus_final_empty_slice(): void
    {
        $key = $this->key('a');
        $sealer = new StreamSealer;
        $header = $sealer->startSeal($key);
        $cipher = $header.$sealer->push($key, '', true);
        $this->assertTrue($sealer->sealed());

        $opener = new StreamSealer;
        $this->assertSame('', $opener->pull($key, $cipher, true));
        $this->assertTrue($opener->pulled());
    }

    public function test_header_only_slice_is_a_legal_intermediate(): void
    {
        $key = $this->key('a');
        $sealer = new StreamSealer;
        $header = $sealer->startSeal($key);
        $body = $sealer->push($key, 'data', false);
        $final = $sealer->push($key, '', true);

        $opener = new StreamSealer;
        $this->assertSame('', $opener->pull($key, $header, false));
        $this->assertSame('data', $opener->pull($key, $body, false));
        $this->assertSame('', $opener->pull($key, $final, true));
    }
}
