<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Teleframe\QueryEngine;

use MeRezaRezaei\Teleframe\QueryEngine\BinderRegistry;
use MeRezaRezaei\Teleframe\QueryEngine\QueryEngine;
use MeRezaRezaei\Teleframe\QueryEngine\Query;
use MeRezaRezaei\Teleframe\QueryEngine\Action;
use MeRezaRezaei\Teleframe\QueryEngine\Policy;
use MeRezaRezaei\Teleframe\QueryEngine\QueryContract;
use PHPUnit\Framework\TestCase;

final class QueryEngineTest extends TestCase
{
    public function testConstructs(): void
    {
        $this->assertInstanceOf(QueryEngine::class, new QueryEngine());
    }

    public function testDetectsMutualRecursion(): void
    {
        $reg = new BinderRegistry();
        $engine = new QueryEngine();

        $mk = function (int $u, int $chat, array $pre, array $post): object {
            return new class (Query::userMessage($u, $chat), new Action('H'.$u), new Policy(), '1') implements QueryContract {
                public function __construct(private Query $q, private Action $a, private Policy $p, private string $v) {}
                public function getQuery(): Query { return $this->q; }
                public function getAction(): Action { return $this->a; }
                public function getPolicy(): Policy { return $this->p; }
                public function getVersion(): string { return $this->v; }
            };
        };

        $reg->bind($mk(1, 2, ['u:1'], ['u:2']), ['u:1'], ['u:2']);
        $reg->bind($mk(2, 1, ['u:2'], ['u:1']), ['u:2'], ['u:1']);

        $patterns = $engine->detectMutualRecursion($reg);
        $this->assertNotEmpty($patterns);
    }

    public function testStateTransition(): void
    {
        $engine = new QueryEngine();
        $this->assertSame('queue', $engine->stateTransition('channel'));
        $this->assertSame('broadcast', $engine->stateTransition('group'));
        $this->assertSame('direct', $engine->stateTransition('private'));
    }
}
