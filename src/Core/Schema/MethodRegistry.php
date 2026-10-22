<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Schema;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;
use MeRezaRezaei\Teleframe\Schema\WireSchemaCoverage;
use RuntimeException;

/**
 * Static registry over the packaged schema artifacts: every method from both
 * schema/methods-mtproto.json and schema/methods-botapi.json, keyed by name.
 *
 * CATALOGUED IS NOT CALLABLE
 * ==========================
 * The artefacts are a faithful record of what Telegram's schema declares, and
 * that record is deliberately broader than this client: the catalogue is
 * generated from `sources/api.tl` (layer 229) while encoding goes through the
 * mirror `TLRegistry` loads, `sources/api_full.tl` (layer 227). A slice of the
 * catalogue therefore names real Telegram methods that this client cannot put
 * on the wire, plus a handful of .tl "functions" that were never endpoints to
 * begin with (generic request envelopes, and `mtproto.tl` service requests the
 * connection layer owns).
 *
 * `has()`/`get()` deliberately keep answering for those names. Removing them
 * would make the catalogue lie in the other direction — a host introspecting
 * the schema would conclude Telegram has no such method — and `describe()`
 * callers rely on `get()` not throwing. So the registry stays complete and the
 * TRUTH is queryable instead:
 *
 *  - `isCallable($name)`      — the honest yes/no a host should gate on
 *  - `unsupportedReason($name)` — why not, in a sentence fit to show a user
 *  - `unsupportedMethods()`   — the whole set, name => reason
 *  - `callableMethods()`      — the advertised-and-really-encodable set
 *
 * Every verdict is derived in `WireSchemaCoverage` from the committed .tl
 * sources, so the reasons cannot rot into folklore. `tests/Schema/
 * CataloguedMethodsAreEncodableTest` closes the loop by asserting that
 * `callableMethods()` and the set that genuinely encodes are the same set —
 * without that test, this gap is exactly what let 34 methods be advertised
 * while being unencodable.
 */
final class MethodRegistry
{
    /** @var array<string, TelegramMethod>|null */
    private static ?array $methods = null;

    /** @var array<string, string>|null name => reason, lazily built */
    private static ?array $unsupported = null;

    /**
     * Load both artifacts (package root schema/). Idempotent: later calls are
     * no-ops once the registry is populated.
     *
     * @throws RuntimeException when an artifact is missing or malformed
     */
    public static function load(): void
    {
        if (self::$methods !== null) {
            return;
        }

        $methods = [];

        foreach (['mtproto' => 'methods-mtproto.json', 'bot-http' => 'methods-botapi.json'] as $api => $file) {
            $path = SchemaArtifacts::path($file);

            $json = file_get_contents($path);
            if ($json === false) {
                throw new RuntimeException("Cannot read schema artifact [{$path}].");
            }

            $envelope = json_decode($json, true);
            if (! is_array($envelope) || ! is_array($envelope['methods'] ?? null)) {
                throw new RuntimeException("Malformed schema artifact [{$path}]: missing methods map.");
            }

            foreach ($envelope['methods'] as $name => $raw) {
                $entry = (array) $raw;
                $entry['api'] = $api;
                $methods[(string) $name] = TelegramMethod::fromArtifact((string) $name, $entry);
            }
        }

        self::$methods = $methods;
        self::$unsupported = null;
    }

    /**
     * Can this client actually put the method on the wire?
     *
     * True for every `bot-http` name: the Bot API is JSON/HTTP, nothing is
     * TL-encoded, so a catalogued entry is callable as it stands. For
     * `mtproto` the answer comes from the wire mirror, which is the closure
     * `TLEncoder` encodes through — not from the catalogue, which is wider.
     */
    public static function isCallable(string $name): bool
    {
        $method = self::get($name);

        return $method->api !== 'mtproto' || WireSchemaCoverage::isCallable($name);
    }

    /**
     * Why the method cannot be called, or null when it can.
     *
     * The reason is written to be shown to a host as-is: it names the file the
     * evidence came from and what the host can do instead.
     *
     * @throws InvalidArgumentException when the name exists in neither artifact
     */
    public static function unsupportedReason(string $name): ?string
    {
        self::load();

        if (! isset(self::$methods[$name])) {
            throw new InvalidArgumentException("Unknown method [{$name}].");
        }

        return self::unsupportedMap()[$name] ?? null;
    }

    /**
     * Every catalogued method that cannot be called, name => reason.
     *
     * @return array<string, string>
     */
    public static function unsupportedMethods(): array
    {
        self::load();

        return self::unsupportedMap();
    }

    /**
     * The set a host may treat as advertised-and-working: everything catalogued
     * minus everything `unsupportedMethods()` names.
     *
     * @return list<string>
     */
    public static function callableMethods(): array
    {
        self::load();
        $unsupported = self::unsupportedMap();

        return array_values(array_filter(
            array_keys(self::$methods ?? []),
            static fn (string $name): bool => ! isset($unsupported[$name]),
        ));
    }

    /**
     * @return array<string, string>
     */
    private static function unsupportedMap(): array
    {
        if (self::$unsupported !== null) {
            return self::$unsupported;
        }

        $map = [];
        foreach (array_keys(self::$methods ?? []) as $name) {
            // Bot API is not TL-encoded, so coverage is an MTProto question.
            if ((self::$methods[$name] ?? null)?->api !== 'mtproto') {
                continue;
            }
            $reason = WireSchemaCoverage::reason($name);
            if ($reason !== null) {
                $map[$name] = $reason;
            }
        }

        return self::$unsupported = $map;
    }

    /**
     * @throws InvalidArgumentException when the name exists in neither artifact
     */
    public static function get(string $name): TelegramMethod
    {
        self::load();

        $method = self::$methods[$name] ?? null;
        if ($method === null) {
            throw new InvalidArgumentException("Unknown method [{$name}].");
        }

        return $method;
    }

    public static function has(string $name): bool
    {
        self::load();

        return isset(self::$methods[$name]);
    }

    /**
     * @return 'mtproto'|'bot-http'
     *
     * @throws InvalidArgumentException when the name exists in neither artifact
     */
    public static function apiOf(string $name): string
    {
        return self::get($name)->api;
    }
}
