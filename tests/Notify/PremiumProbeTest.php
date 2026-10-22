<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Notify\PremiumProbe;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * PremiumProbe (notification-core capability cluster, wave 2): the
 * best-effort users.getFullUser premium probe. Covers the id-matched users
 * vector read (premium true/false), the missing-flag / no-match / throwing
 * paths returning unknown (never throwing), the in-memory TTL cache, and
 * the ttl 0 (always reprobe) / null (cache forever) modes.
 */
final class PremiumProbeTest extends TestCase
{
    public function test_premium_user_in_the_users_vector_id_matched(): void
    {
        $seen = [];
        $calls = 0;
        $probe = new PremiumProbe(static function (string $method, array $params) use (&$seen, &$calls): array {
            $calls++;
            $seen = [$method, $params];

            return ['users' => [['id' => 42, 'premium' => true]]];
        });

        $status = $probe->probe(42);

        self::assertTrue($status->premium());
        self::assertTrue($status->isPremium());
        self::assertSame('users.getFullUser', $seen[0]);
        self::assertSame(['id' => 42], $seen[1]);
        self::assertSame(1, $calls);
    }

    public function test_non_premium_user_in_the_vector(): void
    {
        $probe = new PremiumProbe(static fn (string $method, array $params): array => ['users' => [['id' => 42, 'premium' => false]]]);

        self::assertFalse($probe->probe(42)->premium());
    }

    public function test_other_users_in_the_vector_are_skipped_by_id(): void
    {
        $probe = new PremiumProbe(static fn (string $method, array $params): array => [
            'users' => [
                ['id' => 7, 'premium' => true],
                ['id' => 42, 'premium' => false],
            ],
        ]);

        self::assertFalse($probe->probe(42)->premium());
        self::assertTrue($probe->probe(7)->premium());
    }

    public function test_target_user_without_a_flag_field_yields_unknown(): void
    {
        $probe = new PremiumProbe(static fn (string $method, array $params): array => ['users' => [['id' => 42]]]);

        self::assertFalse($probe->probe(42)->premium()); // unknown = false, conservative
    }

    public function test_no_matching_user_yields_unknown(): void
    {
        $probe = new PremiumProbe(static fn (string $method, array $params): array => ['users' => [['id' => 7, 'premium' => true]]]);

        self::assertFalse($probe->probe(42)->premium());
    }

    public function test_throwing_call_yields_unknown_and_never_throws(): void
    {
        $probe = new PremiumProbe(static function (string $method, array $params): array {
            throw new RuntimeException('transport down');
        });

        self::assertFalse($probe->probe(42)->premium()); // best-effort: never breaks the notify path
    }

    public function test_is_premium_key_fallback(): void
    {
        $probe = new PremiumProbe(static fn (string $method, array $params): array => ['users' => [['id' => 42, 'is_premium' => true]]]);

        self::assertTrue($probe->probe(42)->premium());
    }

    public function test_cache_hit_on_the_second_probe_within_ttl(): void
    {
        $calls = 0;
        $probe = new PremiumProbe(static function (string $method, array $params) use (&$calls): array {
            $calls++;

            return ['users' => [['id' => 42, 'premium' => true]]];
        });

        self::assertTrue($probe->probe(42)->premium());
        self::assertTrue($probe->probe(42)->premium());
        self::assertSame(1, $calls);
    }

    public function test_ttl_zero_reprobes_every_time(): void
    {
        $calls = 0;
        $probe = new PremiumProbe(static function (string $method, array $params) use (&$calls): array {
            $calls++;

            return ['users' => [['id' => 42, 'premium' => true]]];
        }, 0);

        self::assertTrue($probe->probe(42)->premium());
        self::assertTrue($probe->probe(42)->premium());
        self::assertSame(2, $calls);
    }

    public function test_ttl_null_caches_forever(): void
    {
        $calls = 0;
        $probe = new PremiumProbe(static function (string $method, array $params) use (&$calls): array {
            $calls++;

            return ['users' => [['id' => 42, 'premium' => false]]];
        }, null);

        self::assertFalse($probe->probe(42)->premium());
        self::assertFalse($probe->probe(42)->premium());
        self::assertSame(1, $calls);
    }
}
