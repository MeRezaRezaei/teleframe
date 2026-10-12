<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler;

use MeRezaRezaei\Teleframe\Handler\HandlerMatcher;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Middleware\EchoEliminator;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayCache;
use PHPUnit\Framework\TestCase;

final class EchoEliminatorTest extends TestCase
{
    private ArrayCache $cache;
    private EchoEliminator $eliminator;

    protected function setUp(): void
    {
        $registry = new HandlerRegistry();
        $registry->on('updateNewMessage', fn () => null, 0, false);
        $registry->on('updateOwned', fn () => null, 0, true);
        $this->cache = new ArrayCache();
        $this->eliminator = new EchoEliminator($this->cache, new HandlerMatcher($registry));
    }

    public function test_remembered_send_eliminates_echo_by_random_id(): void
    {
        $this->eliminator->remember(1, ['random_id' => 'r123', 'msg_id' => '9', 'sent_at' => 1]);

        $called = false;
        $eliminator = $this->eliminator;
        $result = $eliminator(
            new Update(['_' => 'updateNewMessage', 'random_id' => 'r123'], 1),
            function () use (&$called) {
                $called = true;
            },
        );

        self::assertFalse($called);
        self::assertNull($result);
    }

    public function test_on_own_handler_still_runs_but_flag_is_set(): void
    {
        $this->eliminator->remember(1, ['random_id' => 'r123']);

        $seen = null;
        $eliminator = $this->eliminator;
        $result = $eliminator(
            new Update(['_' => 'updateOwned', 'random_id' => 'r123'], 1),
            function (Update $u) use (&$seen) {
                $seen = $u;

                return 'ran';
            },
        );

        self::assertSame('ran', $result);
        self::assertNotNull($seen);
        self::assertTrue($seen->selfOriginated);
    }

    public function test_unremembered_update_flows_through_self_originated_false(): void
    {
        $seen = null;
        $eliminator = $this->eliminator;
        $eliminator(new Update(['_' => 'updateNewMessage'], 1), function (Update $u) use (&$seen): void {
            $seen = $u;
        });

        self::assertNotNull($seen);
        self::assertFalse($seen->selfOriginated);
    }

    public function test_late_msg_id_reconcile_matches(): void
    {
        $this->eliminator->remember(2, ['msg_id' => '77']);

        $called = false;
        $eliminator = $this->eliminator;
        $eliminator(
            new Update(['_' => 'x', 'msg_id' => '77'], 2),
            function () use (&$called): void {
                $called = true;
            },
        );

        self::assertFalse($called);
    }
}