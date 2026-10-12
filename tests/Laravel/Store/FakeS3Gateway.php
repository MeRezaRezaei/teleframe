<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Store;

use MeRezaRezaei\Teleframe\Laravel\Store\S3GatewayContract;
use RuntimeException;

/**
 * In-memory S3GatewayContract double for the MinIO hot-cache tests:
 * flat key => bytes map with a failure-injection flag that makes every
 * operation throw, standing in for a sealed/down MinIO (this host runs
 * KES/Vault sealed, so the down path is the one that must stay green).
 */
final class FakeS3Gateway implements S3GatewayContract
{
    /** @var array<string, string> */
    private array $objects = [];

    private bool $failing = false;

    public function get(string $key): string|false
    {
        $this->guard();

        return $this->objects[$key] ?? false;
    }

    public function put(string $key, string $bytes): void
    {
        $this->guard();
        $this->objects[$key] = $bytes;
    }

    public function delete(string $key): void
    {
        $this->guard();
        unset($this->objects[$key]);
    }

    public function list(): array
    {
        $this->guard();

        $found = [];
        foreach ($this->objects as $key => $bytes) {
            $found[] = ['key' => $key, 'size' => strlen($bytes)];
        }

        return $found;
    }

    public function failing(bool $failing): void
    {
        $this->failing = $failing;
    }

    /**
     * Tests-only introspection: raw key => bytes map (sidecars included).
     *
     * @return array<string, string>
     */
    public function listAsMap(): array
    {
        $this->guard();

        return $this->objects;
    }

    private function guard(): void
    {
        if ($this->failing) {
            throw new RuntimeException('fake s3 gateway: injected failure');
        }
    }
}
