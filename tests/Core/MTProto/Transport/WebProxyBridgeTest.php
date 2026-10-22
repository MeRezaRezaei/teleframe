<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Tests\MTProto\Transport;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebProxyBridge;
use PHPUnit\Framework\TestCase;

/**
 * Tests for WEB Proxy Bridge URL derivation (tdesktop-web-proxy-bridge-v1).
 *
 * Test vectors from official PROTOCOL.md:
 * https://github.com/telegramdesktop/tproxy-server/blob/master/PROTOCOL.md
 */
class WebProxyBridgeTest extends TestCase
{
    /**
     * Official test vector 1: plain 16-byte secret.
     *
     * Hostname: proxy.example.com
     * Secret: 000102030405060708090a0b0c0d0e0f
     * Expected capability: MHLEY5PmW1GWqJkSrlmJpvJUiLhBH_QKy6yKg8a0JPk
     */
    public function test_derive_capability_official_vector_1(): void
    {
        $capability = WebProxyBridge::deriveCapability(
            'proxy.example.com',
            '000102030405060708090a0b0c0d0e0f'
        );

        $this->assertSame('MHLEY5PmW1GWqJkSrlmJpvJUiLhBH_QKy6yKg8a0JPk', $capability);
    }

    /**
     * Official test vector 2: 17-byte secret with leading 'dd' prefix.
     *
     * Hostname: proxy.example.com
     * Secret: dd000102030405060708090a0b0c0d0e0f
     * Expected capability: IpJrt3e7sKtzPyoXy6w-Zj6GGEvsvclN66JzQEfPYLA
     */
    public function test_derive_capability_official_vector_2(): void
    {
        $capability = WebProxyBridge::deriveCapability(
            'proxy.example.com',
            'dd000102030405060708090a0b0c0d0e0f'
        );

        $this->assertSame('IpJrt3e7sKtzPyoXy6w-Zj6GGEvsvclN66JzQEfPYLA', $capability);
    }

    /**
     * Test bridge URL generation includes the capability.
     */
    public function test_bridge_url_includes_capability(): void
    {
        $url = WebProxyBridge::bridgeUrl(
            'proxy.example.com',
            '000102030405060708090a0b0c0d0e0f'
        );

        $this->assertStringStartsWith('https://proxy.example.com/?bridge=', $url);
        $this->assertStringEndsWith('MHLEY5PmW1GWqJkSrlmJpvJUiLhBH_QKy6yKg8a0JPk', $url);
    }

    /**
     * Test hostname canonicalization: lowercase.
     */
    public function test_hostname_canonicalization_lowercase(): void
    {
        $capability1 = WebProxyBridge::deriveCapability(
            'PROXY.EXAMPLE.COM',
            '000102030405060708090a0b0c0d0e0f'
        );
        $capability2 = WebProxyBridge::deriveCapability(
            'proxy.example.com',
            '000102030405060708090a0b0c0d0e0f'
        );

        $this->assertSame($capability1, $capability2);
    }

    /**
     * Test hostname canonicalization: trailing dot removed.
     */
    public function test_hostname_canonicalization_trailing_dot(): void
    {
        $capability1 = WebProxyBridge::deriveCapability(
            'proxy.example.com.',
            '000102030405060708090a0b0c0d0e0f'
        );
        $capability2 = WebProxyBridge::deriveCapability(
            'proxy.example.com',
            '000102030405060708090a0b0c0d0e0f'
        );

        $this->assertSame($capability1, $capability2);
    }

    /**
     * Test carrier mode normalization accepts valid modes.
     */
    public function test_normalize_carrier_valid_modes(): void
    {
        $validModes = ['https', 'https-lanes', 'websocket', 'websocket-lanes', 'auto'];

        foreach ($validModes as $mode) {
            $normalized = WebProxyBridge::normalizeCarrier($mode);
            $this->assertSame(strtolower($mode), $normalized);
        }
    }

    /**
     * Test carrier mode normalization is case-insensitive.
     */
    public function test_normalize_carrier_case_insensitive(): void
    {
        $this->assertSame('websocket', WebProxyBridge::normalizeCarrier('WEBSOCKET'));
        $this->assertSame('https-lanes', WebProxyBridge::normalizeCarrier('HTTPS-LANES'));
        $this->assertSame('auto', WebProxyBridge::normalizeCarrier('Auto'));
    }

    /**
     * Test carrier mode normalization rejects invalid modes.
     */
    public function test_normalize_carrier_rejects_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid WEB proxy carrier mode');
        WebProxyBridge::normalizeCarrier('invalid-mode');
    }

    /**
     * Test secret validation rejects non-hex input.
     */
    public function test_derive_capability_rejects_non_hex_secret(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('MTProxy secret must be hexadecimal');
        WebProxyBridge::deriveCapability('proxy.example.com', 'not-hex!!!');
    }

    /**
     * Test secret validation rejects wrong length (not 16 or 17 bytes).
     */
    public function test_derive_capability_rejects_wrong_length_secret(): void
    {
        // 15 bytes = 30 hex chars
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('MTProxy secret must be 32 or 34 hex characters');
        WebProxyBridge::deriveCapability('proxy.example.com', '000102030405060708090a0b0c0d0e');

        // 18 bytes = 36 hex chars
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('MTProxy secret must be 32 or 34 hex characters');
        WebProxyBridge::deriveCapability('proxy.example.com', '000102030405060708090a0b0c0d0e0f1011');
    }

    /**
     * Test that leading 'dd' prefix is preserved in secret.
     */
    public function test_dd_prefix_preserved_in_secret(): void
    {
        // These should produce DIFFERENT capabilities because the secret differs
        $capabilityPlain = WebProxyBridge::deriveCapability(
            'proxy.example.com',
            '000102030405060708090a0b0c0d0e0f'
        );
        $capabilityDd = WebProxyBridge::deriveCapability(
            'proxy.example.com',
            'dd000102030405060708090a0b0c0d0e0f'
        );

        $this->assertNotSame($capabilityPlain, $capabilityDd);
    }

    /**
     * Test constant values are correct.
     */
    public function test_constants(): void
    {
        $this->assertSame('tdesktop-web-proxy-bridge-v1', WebProxyBridge::BRIDGE_CONTEXT_LABEL);
        $this->assertSame('auto', WebProxyBridge::DEFAULT_CARRIER);
        $this->assertSame(
            ['https', 'https-lanes', 'websocket', 'websocket-lanes', 'auto'],
            WebProxyBridge::CARRIER_MODES
        );
    }
}
