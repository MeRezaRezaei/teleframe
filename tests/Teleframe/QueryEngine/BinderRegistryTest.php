<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Teleframe\QueryEngine;

use MeRezaRezaei\Teleframe\QueryEngine\Action;
use MeRezaRezaei\Teleframe\QueryEngine\BinderRegistry;
use MeRezaRezaei\Teleframe\QueryEngine\Policy;
use MeRezaRezaei\Teleframe\QueryEngine\Query;
use MeRezaRezaei\Teleframe\QueryEngine\QueryContract;
use PHPUnit\Framework\TestCase;

final class BinderRegistryTest extends TestCase
{
    public function test_bind_and_retrieve(): void
    {
        $reg = new BinderRegistry;
        $contract = new class(Query::userMessage(1, 2), new Action('H'), new Policy, '1') implements QueryContract
        {
            public function __construct(private Query $q, private Action $a, private Policy $p, private string $v) {}

            public function getQuery(): Query
            {
                return $this->q;
            }

            public function getAction(): Action
            {
                return $this->a;
            }

            public function getPolicy(): Policy
            {
                return $this->p;
            }

            public function getVersion(): string
            {
                return $this->v;
            }
        };
        $id = $reg->bind($contract, ['pre'], ['post'], true);
        $this->assertNotNull($reg->get($id));
        $this->assertSame(['pre'], $reg->getPreconditions($id));
        $this->assertSame(['post'], $reg->getPostconditions($id));
        $this->assertTrue($reg->hasBreakCycle($id));
        $this->assertSame(1, $reg->count());
    }
}
