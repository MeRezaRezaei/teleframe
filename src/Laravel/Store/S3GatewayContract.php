<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Store;

/**
 * S3 object gateway for the TStore hot cache (verbatim S6): the seam
 * between the hot-cache tier and object storage. Deliberately NOT a
 * Laravel Storage disk — a thin direct-S3 surface so MinIO speaks
 * path-style on 127.0.0.1 without the framework filesystem stack.
 *
 * Keys are flat "bucket/objectSha/chunkN"; the gateway is pointed at a
 * single configured bucket, so the leading "bucket/" segment is the
 * store-bucket namespace inside it. Reads return false on miss/error —
 * a down gateway reads as a cold cache and the API falls through to
 * Telegram.
 */
interface S3GatewayContract
{
    /** @return string|false object bytes, false on miss or error */
    public function get(string $key): string|false;

    public function put(string $key, string $bytes): void;

    public function delete(string $key): void;

    /**
     * @return list<array{key: string, size: int}>
     */
    public function list(): array;
}
