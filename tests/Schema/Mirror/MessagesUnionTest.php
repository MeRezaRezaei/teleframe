<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use MeRezaRezaei\Teleframe\Repositories\MessagesUnion;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;

/**
 * MessagesUnion EXECUTES its query against the shipped DDL and asserts on the
 * rows that come back.
 *
 * The test this replaces asserted on three substrings of `->toSql()` and
 * never opened a connection, so it was green over a query that cannot run on
 * any driver: the two tables have 22 and 13 columns, and `select *` under
 * UNION ALL is a hard arity error. A string assertion wearing a query's
 * clothes. It came verbatim from
 * docs/superpowers/plans/2026-09-11-telegram-mirror-nf5-generator.md:2093-2107.
 *
 * Provenance: src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php:174,715;
 * src/Schema/Mirror/MirrorCtorSplitter.php:31-64.
 */
final class MessagesUnionTest extends TestCase
{
    private const MIGRATION_MESSAGES = __DIR__.'/../../../src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php';

    /**
     * Run the callback with a sqlite connection whose schema IS the shipped
     * tf_messages / tf_messages_service DDL, then restore the container the
     * Testbench app owned.
     *
     * Nothing about the table shape is retyped here — the migration file is
     * the only copy.
     */
    private function withMessagesTables(callable $fn): void
    {
        $previous = Container::getInstance();

        try {
            $fn(ShippedStoreSchema::capsuleFor(self::MIGRATION_MESSAGES));
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    public function test_peer_history_returns_rows_from_both_tables(): void
    {
        $this->withMessagesTables(function (Capsule $capsule): void {
            $capsule->table('tf_messages')->insert([
                ['account_id' => 7, 'id' => 1, 'peer_type' => 1, 'peer_id' => 42, 'constructor' => 'message', 'date' => 100, 'message' => 'first'],
                ['account_id' => 7, 'id' => 5, 'peer_type' => 1, 'peer_id' => 42, 'constructor' => 'message', 'date' => 500, 'message' => 'fifth'],
            ]);
            $capsule->table('tf_messages_service')->insert([
                ['account_id' => 7, 'id' => 3, 'peer_type' => 1, 'peer_id' => 42, 'constructor' => 'messageService', 'date' => 300, 'reactions_are_possible' => true],
            ]);
            // Noise: another peer and another account, both must stay out.
            $capsule->table('tf_messages')->insert([
                ['account_id' => 7, 'id' => 9, 'peer_type' => 1, 'peer_id' => 99, 'constructor' => 'message', 'date' => 900, 'message' => 'other peer'],
                ['account_id' => 8, 'id' => 10, 'peer_type' => 1, 'peer_id' => 42, 'constructor' => 'message', 'date' => 1000, 'message' => 'other account'],
            ]);

            $rows = MessagesUnion::forPeer(7, 1, 42)->get();

            self::assertCount(3, $rows);

            // Merged ordering across the union boundary, newest id first.
            self::assertSame([5, 3, 1], array_map(static fn ($r): int => (int) $r->id, $rows->all()));
            self::assertSame(
                ['message', 'messageService', 'message'],
                array_map(static fn ($r): string => (string) $r->constructor, $rows->all())
            );

            // A peer row keeps its own text.
            self::assertSame('fifth', $rows[0]->message);

            // The service row's exclusive column survives, and the peer-only
            // columns it does not carry are NULL — not a fabricated false.
            self::assertSame(1, $rows[1]->reactions_are_possible);
            self::assertNull($rows[1]->message);
            self::assertNull($rows[1]->pinned);
            self::assertNull($rows[1]->video_processing_pending);

            // The peer-only column is NULL on the service side of the
            // projection, and the peer row's own value is preserved.
            self::assertNull($rows[2]->reactions_are_possible);
            self::assertSame(0, $rows[2]->pinned);
        });
    }

    public function test_union_projection_spans_every_column_of_both_tables(): void
    {
        $this->withMessagesTables(function (Capsule $capsule): void {
            $capsule->table('tf_messages')->insert([
                ['account_id' => 7, 'id' => 1, 'peer_type' => 1, 'peer_id' => 42, 'constructor' => 'message', 'date' => 100],
            ]);
            $capsule->table('tf_messages_service')->insert([
                ['account_id' => 7, 'id' => 2, 'peer_type' => 1, 'peer_id' => 42, 'constructor' => 'messageService', 'date' => 200],
            ]);

            $row = MessagesUnion::forPeer(7, 1, 42)->firstOrFail();

            $expected = [
                'account_id', 'id', 'peer_type', 'peer_id', 'constructor', 'date',
                'message', 'out', 'mentioned', 'media_unread', 'silent', 'post',
                'from_scheduled', 'legacy', 'edit_hide', 'pinned', 'noforwards',
                'invert_media', 'offline', 'video_processing_pending',
                'paid_suggested_post_stars', 'paid_suggested_post_ton',
                'reactions_are_possible',
            ];

            self::assertSame($expected, array_keys((array) $row));
        });
    }
}
