<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * PassportKeyLoader (design §2/§7/§9/§11, spec §5 G1): the ONLY place the
 * bot RSA private key materializes in the passport module. It owns a PEM
 * **path** (config `teleframe.passport.private_key_path`, env
 * `TELEFRAME_PASSPORT_PRIVATE_KEY`) and resolves it to the key string on
 * first use; the PEM itself never lands in code/config/tests/payloads
 * (AGENTS.md hard rule — sessions/keys are credentials).
 *
 * Hardening (design §11 Q2): the file must be owner-only — permission bits
 * with any group/other bit set (perms & 0o077 !== 0) are REFUSED loud, and
 * a missing/unreadable file is REFUSED loud. Both failures throw
 * PassportLoginException; nothing secret is ever echoed (the path only).
 *
 * The `$stat` seam exists for plain-PHP tests: a closure receiving the
 * path and returning the same shape as PHP's `stat()` (array with a
 * `'mode'` entry — test fakes hand back e.g. `['mode' => 0o100600]`), or
 * `false`/`null` to simulate a missing file. When no seam is injected, the
 * real `stat()` runs. The resolved key is cached in-memory: the PEM is
 * read off disk exactly once per process.
 *
 * Zero regex — the permission mask is pure bit arithmetic.
 */
final class PassportKeyLoader
{
    private ?string $key = null;

    /**
     * @param  string  $path  filesystem path to the bot's RSA private key PEM
     * @param  \Closure(string): (array<string, mixed>|false|null)|null  $stat
     *                                                                          stat() seam for tests — returns a stat()-shaped
     *                                                                          array (with `mode`), or false/null for missing
     */
    public function __construct(
        private readonly string $path,
        private readonly ?\Closure $stat = null,
    ) {}

    /** The configured PEM path (never the key material). */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * The bot RSA private key PEM, read once and cached.
     *
     * @throws PassportLoginException when the PEM is missing, unreadable,
     *                                empty, or not owner-only (0600/0400)
     */
    public function key(): string
    {
        if ($this->key !== null) {
            return $this->key;
        }

        $this->key = $this->loadFromDisk();

        return $this->key;
    }

    /**
     * @throws PassportLoginException
     */
    private function loadFromDisk(): string
    {
        $info = $this->stat !== null
            ? ($this->stat)($this->path)
            : @stat($this->path);

        if ($info === false || $info === null) {
            throw new PassportLoginException(
                'PassportKeyLoader: private key PEM missing at '.$this->path
            );
        }

        $perms = ((int) ($info['mode'] ?? 0)) & 0o777;
        if (($perms & 0o077) !== 0) {
            throw new PassportLoginException(
                'PassportKeyLoader: private key PEM at '.$this->path
                .' must be owner-only (0600/0400), got '.decoct($perms)
            );
        }

        $pem = @file_get_contents($this->path);
        if ($pem === false || $pem === '') {
            throw new PassportLoginException(
                'PassportKeyLoader: private key PEM at '.$this->path.' is unreadable or empty'
            );
        }

        return $pem;
    }
}
