<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Passport\PassportKeyLoader;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use PHPUnit\Framework\TestCase;

/**
 * PassportKeyLoaderTest — the ONLY place the bot RSA private key materializes
 * (design §2/§7/§9/§11): PEM path → key string, 0600/0400 owner-only
 * enforcement (perms & 0o077 → loud), missing/unreadable file → loud, and
 * the in-memory cache (the PEM is read off disk exactly once per process).
 * Nothing secret is ever echoed — the path only. The `$stat` seam lets the
 * tests drive permission/missing outcomes without real file metadata.
 */
final class PassportKeyLoaderTest extends TestCase
{
    private string $pemFile = '';

    protected function setUp(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        self::assertNotFalse($key);
        $exported = openssl_pkey_export($key, $pem);
        self::assertTrue($exported);

        $this->pemFile = tempnam(sys_get_temp_dir(), 'teleframe-passport-key-');
        self::assertNotFalse($this->pemFile);
        file_put_contents($this->pemFile, $pem);
        chmod($this->pemFile, 0o600);
    }

    protected function tearDown(): void
    {
        if ($this->pemFile !== '' && is_file($this->pemFile)) {
            unlink($this->pemFile);
        }
    }

    public function test_path_accessor_returns_the_configured_path(): void
    {
        $loader = new PassportKeyLoader($this->pemFile);

        self::assertSame($this->pemFile, $loader->path());
    }

    public function test_key_reads_an_owner_only_pem_from_disk(): void
    {
        $loader = new PassportKeyLoader($this->pemFile);

        $key = $loader->key();

        self::assertStringContainsString('BEGIN PRIVATE KEY', $key);
        self::assertStringContainsString('END PRIVATE KEY', $key);
    }

    public function test_key_is_read_off_disk_exactly_once(): void
    {
        $statCalls = 0;
        $loader = new PassportKeyLoader($this->pemFile, static function (string $path) use (&$statCalls): array {
            $statCalls++;

            return ['mode' => 0o100600, 'size' => filesize($path)];
        });

        $first = $loader->key();
        $second = $loader->key();

        self::assertSame($first, $second);
        self::assertSame(1, $statCalls);
    }

    public function test_group_or_other_permission_bits_are_refused_loud(): void
    {
        chmod($this->pemFile, 0o644);

        $loader = new PassportKeyLoader($this->pemFile);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('must be owner-only (0600/0400), got 644');

        $loader->key();
    }

    public function test_seam_permission_bits_govern_even_when_the_file_is_0600(): void
    {
        $loader = new PassportKeyLoader($this->pemFile, static fn (string $path): array => [
            'mode' => 0o100644,
            'size' => filesize($path),
        ]);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('must be owner-only');

        $loader->key();
    }

    public function test_missing_file_is_refused_loud_with_the_path(): void
    {
        $missing = $this->pemFile.'.nope';
        $loader = new PassportKeyLoader($missing);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('private key PEM missing at '.$missing);

        $loader->key();
    }

    public function test_stat_seam_false_is_refused_as_a_missing_file(): void
    {
        $loader = new PassportKeyLoader($this->pemFile, static fn (string $path) => false);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('private key PEM missing at '.$this->pemFile);

        $loader->key();
    }

    public function test_empty_or_unreadable_file_is_refused_loud(): void
    {
        $empty = $this->pemFile.'.empty';
        file_put_contents($empty, '');
        chmod($empty, 0o600);

        try {
            $loader = new PassportKeyLoader($empty);

            $this->expectException(PassportLoginException::class);
            $this->expectExceptionMessage('unreadable or empty');

            $loader->key();
        } finally {
            unlink($empty);
        }
    }

    public function test_seam_without_a_mode_entry_falls_through_to_the_real_file(): void
    {
        // The seam contract is "stat()-shaped array (with `mode`)". A seam
        // that omits mode reads as perms 0 — no group/other bits set — so the
        // owner-only gate passes and the real file is read (actual shipped
        // behavior: `$info['mode'] ?? 0`). Real stat() always carries mode.
        $loader = new PassportKeyLoader($this->pemFile, static fn (string $path): array => [
            'size' => filesize($path),
        ]);

        $key = $loader->key();

        self::assertStringContainsString('BEGIN PRIVATE KEY', $key);
    }
}
