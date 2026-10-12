<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Teleframe\Control;

use MeRezaRezaei\Teleframe\Control\RelayAccountsCatalog;
use MeRezaRezaei\Teleframe\Control\RelaySessionRegistry;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use PHPUnit\Framework\TestCase;

/**
 * The "accounts in action" catalog: presence comes purely from the injected
 * RelaySessionRegistry (never a dial) and display identity from the injected
 * resolver, so the plain-PHP construction contract holds and the live switch
 * menu is testable without a container, DB, or any session string.
 */
final class RelayAccountsCatalogTest extends TestCase
{
    private function registry(): RelaySessionRegistry
    {
        $registry = new RelaySessionRegistry(new InMemoryCache, 300);
        $registry->start(1, 'relay-a');
        $registry->start(2, 'relay-b');

        return $registry;
    }

    public function test_live_lists_each_live_account_with_resolved_identity(): void
    {
        $registry = $this->registry();
        $catalog = new RelayAccountsCatalog(
            $registry,
            static function (array $ids): array {
                $rows = [];
                foreach ($ids as $id) {
                    $rows[$id] = ['account_id' => $id, 'label' => 'account-'.$id, 'dc_id' => 2];
                }

                return $rows;
            },
        );

        $live = $catalog->live();

        self::assertCount(2, $live);
        self::assertSame([1, 2], array_column($live, 'account_id'));
        self::assertSame(['account-1', 'account-2'], array_column($live, 'label'));
        self::assertTrue($live[0]['online']);
    }

    public function test_live_omits_accounts_the_resolver_cannot_identify(): void
    {
        $registry = $this->registry();
        $catalog = new RelayAccountsCatalog(
            $registry,
            static fn (array $ids): array => [$ids[0] => ['account_id' => $ids[0], 'label' => 'only-1', 'dc_id' => 2]],
        );

        self::assertCount(1, $catalog->live());
        self::assertSame([1], array_column($catalog->live(), 'account_id'));
    }

    public function test_live_returns_empty_without_sessions_and_never_dials(): void
    {
        $registry = new RelaySessionRegistry(new InMemoryCache);
        $catalog = new RelayAccountsCatalog(
            $registry,
            static function (array $ids): array {
                throw new \RuntimeException('the resolver must not be called with no live ids');
            },
        );

        self::assertSame([], $catalog->live());
    }

    public function test_catalog_never_carries_a_session_string(): void
    {
        $registry = $this->registry();
        $catalog = new RelayAccountsCatalog(
            $registry,
            static fn (array $ids): array => [(string) $ids[0] => ['account_id' => $ids[0], 'label' => 'x', 'dc_id' => 2]],
        );

        $live = $catalog->live();
        $json = json_encode($live);

        self::assertIsString($json);
        self::assertStringNotContainsString('session', $json);
    }
}
