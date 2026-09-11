<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use MeRezaRezaei\Teleframe\Repositories\MessagesUnion;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

final class MessagesUnionTest extends TestbenchTestCase
{
    public function test_union_sql_spans_both_tables(): void
    {
        $sql = MessagesUnion::forPeer(7, 1, 42)->toSql();
        self::assertStringContainsString('tf_messages', $sql);
        self::assertStringContainsString('tf_messages_service', $sql);
        self::assertStringContainsString('union all', strtolower($sql));
    }
}
