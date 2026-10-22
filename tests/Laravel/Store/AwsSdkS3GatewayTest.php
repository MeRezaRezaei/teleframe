<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Store;

use Aws\MockHandler;
use Aws\Result;
use Aws\S3\S3Client;
use MeRezaRezaei\Teleframe\Laravel\Store\AwsSdkS3Gateway;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Direct unit tests for the real S3 driver AwsSdkS3Gateway (TStore hot
 * cache). The gateway lazy-news its own S3Client on first use and never
 * in the constructor, so tests pre-bind an already-built client through
 * the withClient() test seam. The bound client is a REAL Aws\S3\S3Client
 * driven by the SDK's own MockHandler queue — real command plumbing,
 * zero network. Covers the put/get/delete roundtrip, the
 * swallowed-Throwable->false read contract (why a down gateway reads as
 * a cold cache and the API falls through to Telegram), the write
 * propagation contract, and list() page flattening. The missing-SDK
 * branch (class_exists gate inside client()) is env-blocked here:
 * aws/aws-sdk-php ships as a loaded dev dependency, so that gate can
 * never be false in the test environment.
 */
final class AwsSdkS3GatewayTest extends TestCase
{
    private function gateway(S3Client $client): AwsSdkS3Gateway
    {
        return (new AwsSdkS3Gateway([
            'endpoint' => 'http://127.0.0.1:9000',
            'region' => 'us-east-1',
            'key' => 'test-key',
            'secret' => 'test-secret',
            'bucket' => 'tf-test',
            'use_path_style' => true,
        ]))->withClient($client);
    }

    private function client(MockHandler $handler): S3Client
    {
        return new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
            'endpoint' => 'http://127.0.0.1:9000',
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => 'test-key',
                'secret' => 'test-secret',
            ],
            'handler' => $handler,
        ]);
    }

    public function test_put_and_get_roundtrip(): void
    {
        $mock = new MockHandler([
            new Result([]),
            new Result(['Body' => 'hello bytes']),
        ]);
        $gateway = $this->gateway($this->client($mock));

        $gateway->put('sha/chunk-0', 'hello bytes');

        $this->assertSame('hello bytes', $gateway->get('sha/chunk-0'));
    }

    public function test_get_missing_object_returns_false(): void
    {
        $mock = new MockHandler([new Result([])]);
        $gateway = $this->gateway($this->client($mock));

        $this->assertFalse($gateway->get('sha/missing'));
    }

    public function test_get_swallows_throwable_and_returns_false(): void
    {
        $mock = new MockHandler([]);
        $mock->appendException(new RuntimeException('s3 down'));
        $gateway = $this->gateway($this->client($mock));

        $this->assertFalse($gateway->get('sha/chunk-0'));
    }

    public function test_put_propagates_client_failure(): void
    {
        $mock = new MockHandler([]);
        $mock->appendException(new RuntimeException('s3 down'));
        $gateway = $this->gateway($this->client($mock));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('s3 down');

        $gateway->put('sha/chunk-0', 'bytes');
    }

    public function test_delete_roundtrip(): void
    {
        $mock = new MockHandler([new Result([])]);
        $gateway = $this->gateway($this->client($mock));

        $gateway->delete('sha/chunk-0');

        $this->assertSame('DeleteObject', $mock->getLastCommand()->getName());
    }

    public function test_delete_propagates_client_failure(): void
    {
        $mock = new MockHandler([]);
        $mock->appendException(new RuntimeException('s3 down'));
        $gateway = $this->gateway($this->client($mock));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('s3 down');

        $gateway->delete('sha/chunk-0');
    }

    public function test_list_flattens_objects_across_pages(): void
    {
        $mock = new MockHandler([
            new Result(['Contents' => [
                ['Key' => 'sha/one', 'Size' => 3],
                ['Key' => 'sha/two', 'Size' => 0],
            ], 'NextContinuationToken' => 'tok2']),
            new Result(['Contents' => [], 'NextContinuationToken' => 'tok3']),
            new Result(['Contents' => [['Key' => 'sha/three', 'Size' => 5]]]),
        ]);
        $gateway = $this->gateway($this->client($mock));

        $this->assertSame([
            ['key' => 'sha/one', 'size' => 3],
            ['key' => 'sha/two', 'size' => 0],
            ['key' => 'sha/three', 'size' => 5],
        ], $gateway->list());
    }

    public function test_list_empty_bucket_returns_empty(): void
    {
        $mock = new MockHandler([new Result([])]);
        $gateway = $this->gateway($this->client($mock));

        $this->assertSame([], $gateway->list());
    }

    public function test_with_client_returns_same_instance(): void
    {
        $gateway = $this->gateway($this->client(new MockHandler));

        $this->assertSame($gateway, $gateway->withClient($this->client(new MockHandler)));
    }
}
