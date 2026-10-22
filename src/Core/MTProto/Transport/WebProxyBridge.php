<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\MTProto\Transport;

use InvalidArgumentException;

/**
 * WEB Proxy Bridge URL derivation (tdesktop-web-proxy-bridge-v1).
 *
 * Implements the official bridge capability derivation from Telegram Desktop:
 * https://github.com/telegramdesktop/tproxy-server/blob/master/PROTOCOL.md
 *
 * The capability is an HMAC-SHA256 of the context string:
 * "tdesktop-web-proxy-bridge-v1\n" + canonicalized hostname
 * keyed by the MTProxy secret (hex-decoded, retaining leading 'dd' if present).
 *
 * The result is base64url-encoded without padding.
 */
class WebProxyBridge
{
    /** The frozen v1 context label from the official protocol. */
    public const BRIDGE_CONTEXT_LABEL = 'tdesktop-web-proxy-bridge-v1';

    /** Default carrier mode when 'auto' is configured. */
    public const DEFAULT_CARRIER = 'auto';

    /** Supported carrier modes. */
    public const CARRIER_MODES = ['https', 'https-lanes', 'websocket', 'websocket-lanes', 'auto'];

    /**
     * Derive the bridge capability token from hostname and MTProxy secret.
     *
     * @param  string  $hostname  Proxy hostname (e.g., 'proxy.example.com')
     * @param  string  $secretHex  MTProxy secret in hex (16 bytes = 32 hex chars, or 17 bytes with leading 'dd' = 34 hex chars)
     * @return string Base64url-encoded HMAC-SHA256 capability token
     */
    public static function deriveCapability(string $hostname, string $secretHex): string
    {
        // Decode secret hex to bytes (retain leading 'dd' if present)
        $secretBytes = self::decodeSecretHex($secretHex);

        // Canonical hostname: lowercase ASCII/IDNA A-label, no trailing dot
        $canonicalHost = self::canonicalizeHostname($hostname);

        // Context: "tdesktop-web-proxy-bridge-v1\n" + hostname
        $context = self::BRIDGE_CONTEXT_LABEL."\n".$canonicalHost;

        // HMAC-SHA256(key=secretBytes, message=context)
        $hmac = hash_hmac('sha256', $context, $secretBytes, true);

        // base64url-no-padding
        return rtrim(strtr(base64_encode($hmac), '+/', '-_'), '=');
    }

    /**
     * Build the full bridge URL for a WEB proxy.
     *
     * @param  string  $hostname  Proxy hostname
     * @param  string  $secretHex  MTProxy secret in hex
     * @return string Full bridge URL (e.g., 'https://proxy.example.com/?bridge=MHLEY5PmW1GWqJkSrlmJpvJUiLhBH_QKy6yKg8a0JPk')
     */
    public static function bridgeUrl(string $hostname, string $secretHex): string
    {
        $capability = self::deriveCapability($hostname, $secretHex);
        $canonicalHost = self::canonicalizeHostname($hostname);

        return "https://{$canonicalHost}/?bridge={$capability}";
    }

    /**
     * Validate and normalize carrier mode.
     *
     * @param  string  $carrier  Carrier mode (https, https-lanes, websocket, websocket-lanes, auto)
     * @return string Normalized carrier mode
     */
    public static function normalizeCarrier(string $carrier): string
    {
        $carrier = strtolower($carrier);
        if (! in_array($carrier, self::CARRIER_MODES, true)) {
            throw new InvalidArgumentException("Invalid WEB proxy carrier mode: {$carrier}. Supported: ".implode(', ', self::CARRIER_MODES));
        }

        return $carrier;
    }

    /**
     * Decode MTProxy secret from hex, preserving leading 'dd' prefix if present.
     *
     * Official format:
     * - 16-byte secret: 32 hex chars (plain MTProxy secret)
     * - 17-byte secret: 34 hex chars with leading 'dd' (dd-secret for FakeTLS-compatible proxies)
     *
     * @return string Raw binary secret
     */
    private static function decodeSecretHex(string $secretHex): string
    {
        $secretHex = strtolower(trim($secretHex));

        if (! ctype_xdigit($secretHex)) {
            throw new InvalidArgumentException('MTProxy secret must be hexadecimal');
        }

        $len = strlen($secretHex);
        if ($len !== 32 && $len !== 34) {
            throw new InvalidArgumentException("MTProxy secret must be 32 or 34 hex characters (16 or 17 bytes), got {$len}");
        }

        return hex2bin($secretHex);
    }

    /**
     * Canonicalize hostname per RFC 3986 / IDNA2008.
     * Lowercase ASCII, IDNA A-label for internationalized domains, no trailing dot.
     */
    private static function canonicalizeHostname(string $hostname): string
    {
        $hostname = trim($hostname, '.');

        // Try IDNA conversion for internationalized domains
        $ascii = @idn_to_ascii($hostname, IDNA_NONTRANSITIONAL_TO_ASCII | IDNA_USE_STD3_RULES);
        if ($ascii !== false && $ascii !== '') {
            return strtolower($ascii);
        }

        // Fallback: basic lowercase for ASCII hostnames
        return strtolower($hostname);
    }
}
