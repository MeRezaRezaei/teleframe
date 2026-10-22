<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Store;

use Aws\S3\S3Client;
use RuntimeException;

/**
 * aws/aws-sdk-php backed S3GatewayContract. The SDK ships as a dev
 * dependency, so the client is lazy-initialized on first real use —
 * never in the constructor — and a missing SDK blows up only when the
 * gateway is actually exercised, keeping plain-PHP bootstrap cheap.
 * Always path-style endpoints (MinIO on 127.0.0.1 ignores virtual
 * hosts).
 */
final class AwsSdkS3Gateway implements S3GatewayContract
{
    private ?S3Client $client = null;

    /**
     * @param  array{endpoint: string, region: string, key: string, secret: string, bucket: string, use_path_style: bool}  $config
     */
    public function __construct(private readonly array $config) {}

    /**
     * Test seam: pre-bind an already-built S3Client so unit tests can
     * drive the gateway without constructing the real SDK client (and
     * without hitting the network). Production never calls this — the
     * client stays lazy-initialized on first real use.
     */
    public function withClient(S3Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Introspection seam: the RESOLVED configuration, after the
     * provider's `??` fallbacks have been applied.
     *
     * This exists because endpoint/region/bucket used to be LITERALS in
     * the S3GatewayContract binding, so "is it configurable?" was
     * unanswerable — there was no way to assert a configured value
     * against a value the code had baked in. Reading it back is the only
     * place the configured chain and the shipped defaults can both be
     * checked. Deliberately NOT on S3GatewayContract: a transport-agnostic
     * gateway seam has no configuration to expose, and adding it would
     * force every implementation (the hot-cache double included) to
     * grow a method nothing in production calls.
     *
     * @return array{endpoint: string, region: string, key: string, secret: string, bucket: string, use_path_style: bool}
     */
    public function config(): array
    {
        return $this->config;
    }

    public function get(string $key): string|false
    {
        try {
            $result = $this->client()->getObject([
                'Bucket' => $this->config['bucket'],
                'Key' => $key,
            ]);
            $body = $result['Body'] ?? null;
            $bytes = $body === null ? null : (string) $body;

            return $bytes === null ? false : $bytes;
        } catch (\Throwable) {
            return false;
        }
    }

    public function put(string $key, string $bytes): void
    {
        $this->client()->putObject([
            'Bucket' => $this->config['bucket'],
            'Key' => $key,
            'Body' => $bytes,
        ]);
    }

    public function delete(string $key): void
    {
        $this->client()->deleteObject([
            'Bucket' => $this->config['bucket'],
            'Key' => $key,
        ]);
    }

    public function list(): array
    {
        $found = [];
        $paginator = $this->client()->getPaginator('ListObjectsV2', [
            'Bucket' => $this->config['bucket'],
        ]);

        foreach ($paginator as $page) {
            $contents = $page->get('Contents') ?? [];
            foreach ($contents as $object) {
                $found[] = ['key' => (string) $object['Key'], 'size' => (int) ($object['Size'] ?? 0)];
            }
        }

        return $found;
    }

    private function client(): S3Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        if (! class_exists(S3Client::class)) {
            throw new RuntimeException('aws/aws-sdk-php not installed');
        }

        $this->client = new S3Client([
            'version' => 'latest',
            'region' => $this->config['region'],
            'endpoint' => $this->config['endpoint'],
            'use_path_style_endpoint' => $this->config['use_path_style'],
            'credentials' => [
                'key' => $this->config['key'],
                'secret' => $this->config['secret'],
            ],
        ]);

        return $this->client;
    }
}
