<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Message;

use DateInterval;
use DateTimeImmutable;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

/**
 * PSR-16 filesystem store for the Message layer's compiled-plan cache
 * (`MessageCompiler`). The `bootstrap/cache`-style analogue: one `.cache`
 * file per key inside a named directory, persistent across processes —
 * unlike the handler layer's process-scoped `InMemoryCache` null-object.
 *
 * Values are serialized arrays (`unserialize(..., allowed_classes: false)` —
 * the store never writes objects, so object injection stays a non-issue).
 * TTL is honoured lazily: an expired entry is dropped on `get()`/`has()`.
 * All file I/O goes through `Illuminate\Filesystem\Filesystem`.
 *
 * Cache keys are the PSR-16 key namespace (`A-Za-z0-9_.`, max 64 chars),
 * checked with `strcspn` — zero-regex per the engine rule.
 */
final class FilesystemCache implements CacheInterface
{
    private const EXTENSION = '.cache';

    private readonly string $directory;

    private readonly Filesystem $filesystem;

    public function __construct(string $directory, ?Filesystem $filesystem = null)
    {
        $this->directory = rtrim($directory, '/');
        $this->filesystem = $filesystem ?? new Filesystem();

        if (! $this->filesystem->isDirectory($this->directory)) {
            $this->filesystem->makeDirectory($this->directory, 0755, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertKey($key);

        $payload = $this->read($key);
        if ($payload === null) {
            return $default;
        }

        if ($payload['expires'] !== 0 && $payload['expires'] < time()) {
            $this->delete($key);

            return $default;
        }

        return $payload['value'];
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->assertKey($key);

        $seconds = self::ttlSeconds($ttl);
        $expires = $seconds === 0 ? 0 : time() + $seconds;

        $envelope = serialize(['expires' => $expires, 'value' => $value]);

        $written = $this->filesystem->put($this->pathFor($key), $envelope, true);

        return $written !== false;
    }

    public function delete(string $key): bool
    {
        $this->assertKey($key);

        $path = $this->pathFor($key);
        if (! $this->filesystem->isFile($path)) {
            return false;
        }

        $this->filesystem->delete($path);

        return true;
    }

    public function clear(): bool
    {
        $cleared = true;

        foreach ($this->files() as $path) {
            if (! $this->filesystem->delete($path)) {
                $cleared = false;
            }
        }

        return $cleared;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $found = [];

        foreach ($keys as $key) {
            $found[(string) $key] = $this->get((string) $key, $default);
        }

        return $found;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $ok = true;

        foreach ($values as $key => $value) {
            if (! $this->set((string) $key, $value, $ttl)) {
                $ok = false;
            }
        }

        return $ok;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $ok = true;

        foreach ($keys as $key) {
            if (! $this->delete((string) $key)) {
                $ok = false;
            }
        }

        return $ok;
    }

    public function has(string $key): bool
    {
        $this->assertKey($key);

        $payload = $this->read($key);
        if ($payload === null) {
            return false;
        }

        if ($payload['expires'] !== 0 && $payload['expires'] < time()) {
            $this->delete($key);

            return false;
        }

        return true;
    }

    /** Absolute paths of all cache files in the store directory. */
    public function files(): array
    {
        $paths = [];

        foreach ($this->filesystem->files($this->directory) as $file) {
            $paths[] = $file->getPathname();
        }

        return $paths;
    }

    /**
     * @return array{expires: int, value: mixed}|null null when unset/corrupt
     */
    private function read(string $key): ?array
    {
        $path = $this->pathFor($key);
        if (! $this->filesystem->isFile($path)) {
            return null;
        }

        $decoded = unserialize((string) $this->filesystem->get($path), ['allowed_classes' => false]);
        if (! is_array($decoded) || ! array_key_exists('expires', $decoded) || ! array_key_exists('value', $decoded)) {
            return null;
        }

        $expires = $decoded['expires'];

        return [
            'expires' => is_int($expires) ? $expires : 0,
            'value' => $decoded['value'],
        ];
    }

    private function pathFor(string $key): string
    {
        return $this->directory . '/' . $key . self::EXTENSION;
    }

    private function assertKey(string $key): void
    {
        if ($key === '') {
            throw new InvalidArgumentException('Cache key must be a non-empty string.');
        }
        if (strlen($key) > 64) {
            throw new InvalidArgumentException('Cache key must not exceed 64 characters.');
        }
        if (strcspn($key, '{}()/\@:') !== strlen($key)) {
            throw new InvalidArgumentException(sprintf(
                'Cache key [%s] contains a character reserved by PSR-16 ({}()/\\@:).',
                $key
            ));
        }
    }

    private static function ttlSeconds(null|int|DateInterval $ttl): int
    {
        if ($ttl === null) {
            return 0;
        }

        if (is_int($ttl)) {
            return $ttl > 0 ? $ttl : 0;
        }

        $now = new DateTimeImmutable();
        $seconds = $now->add($ttl)->getTimestamp() - $now->getTimestamp();

        return $seconds > 0 ? $seconds : 0;
    }
}