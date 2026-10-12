<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\BotMap;

use MeRezaRezaei\Teleframe\Backup\InMemoryVault;
use MeRezaRezaei\Teleframe\BotMap\TokenVault;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TokenVaultTest extends TestCase
{
    public function test_store_and_retrieve_round_trip(): void
    {
        $vault = new TokenVault(new InMemoryVault(), 'passphrase');

        $vault->store('ops-prod', '123:SUPERSECRET');

        self::assertSame('123:SUPERSECRET', $vault->retrieve('ops-prod'));
    }

    public function test_retrieve_requires_the_same_passphrase(): void
    {
        $memory = new InMemoryVault();
        (new TokenVault($memory, 'right'))->store('k', '123:TOKEN');

        $this->expectException(RuntimeException::class);
        (new TokenVault($memory, 'wrong'))->retrieve('k');
    }

    public function test_has_detects_a_stored_token_without_decrypting(): void
    {
        $memory = new InMemoryVault();
        $vault = new TokenVault($memory, 'pass');

        self::assertFalse($vault->has('k'));
        $vault->store('k', '123:TOKEN');
        self::assertTrue($vault->has('k'));
    }

    public function test_retrieve_unknown_key_throws(): void
    {
        $vault = new TokenVault(new InMemoryVault(), 'pass');

        $this->expectException(RuntimeException::class);
        $vault->retrieve('missing');
    }

    public function test_store_overwrites_the_same_key(): void
    {
        $vault = new TokenVault(new InMemoryVault(), 'pass');
        $vault->store('k', 'old:token');
        $vault->store('k', 'new:token');

        self::assertSame('new:token', $vault->retrieve('k'));
    }

    public function test_store_rejects_an_empty_token(): void
    {
        $vault = new TokenVault(new InMemoryVault(), 'pass');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot vault an empty bot token.');
        $vault->store('k', '');
    }
}