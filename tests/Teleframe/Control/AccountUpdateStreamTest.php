<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Teleframe\Control;

use Illuminate\Database\Eloquent\Model;
use LogicException;
use MeRezaRezaei\Teleframe\Control\AccountUpdateStream;
use MeRezaRezaei\Teleframe\Control\RelayFilter;
use MeRezaRezaei\Teleframe\Control\RelayMessage;
use PHPUnit\Framework\TestCase;

/**
 * D-044 — the control-layer update stream.
 *
 * The reader yields the IDENTITY of a committed `tf_updates` row
 * (`{account_id, seq, position}`), never a decoded update array, and the
 * injected hydrator resolves that key to the committed model. The RelayMessage
 * carries the identity row; `raw:` stays null because this leg was designed
 * for a non-payload message and `tf_updates` stores ZERO JSON
 * (`2026_09_14_200030:16-18`), so the raw array is not reconstructible.
 *
 * NULL SEMANTICS are the other half of the contract and each is a different
 * condition: no reader → null, drained reader → null, key with no row → null
 * (a missing row is a fact, not a wiring error), and reader-without-hydrator →
 * LogicException (the mis-wiring the old contract turned into a SILENTLY EMPTY
 * RELAY).
 *
 * Pure plain-PHP: no container, no credentials, no live bus.
 */
final class AccountUpdateStreamTest extends TestCase
{
    public function test_all_optional_deps_are_noop_defaults_constructing_without_container(): void
    {
        $stream = new AccountUpdateStream(1);

        self::assertSame(1, $stream->accountId);
        $stream->start();
        self::assertNull($stream->next());
        $stream->stop();
    }

    public function test_start_next_stop_orders_registry_lifecycle(): void
    {
        $calls = [];
        $stream = new AccountUpdateStream(
            7,
            self::reader([self::key(9, 1), self::key(9, 2)]),
            self::hydrator(),
            null,
            self::recorder($calls, 'start'),
            self::recorder($calls, 'stop'),
            self::recorder($calls, 'touch'),
        );

        $stream->start();
        self::assertNotNull($stream->next());
        self::assertNotNull($stream->next());
        self::assertNull($stream->next(), 'drained reader yields null, not a third touch');
        $stream->stop();

        self::assertSame(['start(7,stream)', 'touch(7)', 'touch(7)', 'stop(7)'], $calls);
    }

    public function test_each_committed_row_becomes_an_inbound_relay_message_carrying_the_identity_row(): void
    {
        $published = [];
        $stream = new AccountUpdateStream(
            7,
            self::reader([self::key(9, 0)]),
            self::hydrator(['constructor' => 'updateNewMessage']),
            static function (RelayMessage $message) use (&$published): void {
                $published[] = $message;
            },
        );

        $message = $stream->next();

        self::assertInstanceOf(RelayMessage::class, $message);
        self::assertSame(RelayMessage::INBOUND, $message->direction);
        self::assertSame('update', $message->method);
        self::assertTrue($message->isInbound());
        self::assertNull($message->raw, 'this leg was built for a non-payload message; raw stays null');
        self::assertCount(1, $published);
        self::assertSame($message, $published[0], 'publisher sees the very message the pipeline produced');
    }

    // ── D-044: the key IS the payload, the decoded array is not ─────────

    public function test_the_relay_message_carries_the_hydrated_row_not_a_decoded_update_array(): void
    {
        $stream = new AccountUpdateStream(
            7,
            self::reader([self::key(9, 0)]),
            self::hydrator(['constructor' => 'updateNewMessage']),
        );

        $message = $stream->next();

        self::assertNotNull($message);
        self::assertSame(
            [
                'account_id' => 7,
                'seq' => 9,
                'position' => 0,
                'constructor' => 'updateNewMessage',
            ],
            $message->params,
            'the committed identity row is the payload — a decoded update array is not reconstructible '
            .'from tf_updates because it stores zero JSON',
        );
    }

    public function test_the_hydrator_receives_exactly_the_key_the_reader_yielded(): void
    {
        $seen = [];
        $key = self::key(11, 3);

        $stream = new AccountUpdateStream(
            7,
            static fn (): ?array => $key,
            static function (array $received) use (&$seen): ?Model {
                $seen[] = $received;

                return self::row(['constructor' => 'updateEditMessage']);
            },
        );

        self::assertNotNull($stream->next());
        self::assertSame([self::key(11, 3)], $seen, 'one hydrate call per key, verbatim — no reshaping');
    }

    public function test_a_row_attribute_wins_over_the_key_and_the_key_fills_only_what_the_row_omits(): void
    {
        // A hydrate that projects a PARTIAL row must still be addressable by
        // the identity the bus published.
        $stream = new AccountUpdateStream(
            7,
            self::reader([['account_id' => 7, 'seq' => 9, 'position' => 4]]),
            self::hydrator(['seq' => 9]),
        );

        $message = $stream->next();

        self::assertNotNull($message);
        self::assertSame(['account_id' => 7, 'seq' => 9, 'position' => 4], $message->params);
    }

    // ── the loud path: reader wired, hydrator not ───────────────────────

    public function test_a_reader_with_no_hydrator_throws_loud_instead_of_relaying_an_empty_message(): void
    {
        $stream = new AccountUpdateStream(7, self::reader([self::key(9, 0)]));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('was given a reader with no hydrator');

        $stream->next();
    }

    public function test_the_refusal_names_the_account_and_the_seam_to_wire(): void
    {
        $stream = new AccountUpdateStream(4242, self::reader([self::key(9, 0)]));

        try {
            $stream->next();
            self::fail('a reader with no hydrator must refuse, never publish');
        } catch (LogicException $e) {
            self::assertStringContainsString('4242', $e->getMessage(), 'the refusal names the account');
            self::assertStringContainsString('$hydrate', $e->getMessage(), 'the refusal names the seam');
        }
    }

    public function test_the_drained_reader_still_yields_null_and_never_reaches_the_loud_path(): void
    {
        $stream = new AccountUpdateStream(7, self::reader([]));

        self::assertNull($stream->next(), 'no key means drained, not a wiring error');
    }

    // ── a key with no row degrades to null, it does not throw ───────────

    public function test_a_key_naming_no_row_degrades_to_null_without_publishing(): void
    {
        $published = [];
        $touched = [];
        $stream = new AccountUpdateStream(
            7,
            self::reader([self::key(9, 0)]),
            static fn (array $key): ?Model => null,
            static function (RelayMessage $message) use (&$published): void {
                $published[] = $message;
            },
            null,
            null,
            static function (int $accountId) use (&$touched): void {
                $touched[] = $accountId;
            },
        );

        self::assertNull($stream->next(), 'a pruned row is a fact about the world, not a wiring error');
        self::assertSame([], $published, 'nothing is published for a row that is not there');
        self::assertSame([7], $touched, 'the key WAS read off the reader — that is still a live pulse');
    }

    public function test_a_missing_row_among_live_ones_does_not_end_the_stream(): void
    {
        $stream = new AccountUpdateStream(
            7,
            self::reader([self::key(9, 0), self::key(9, 1)]),
            static function (array $key): ?Model {
                return $key['position'] === 0 ? null : self::row(['constructor' => 'updateEditMessage']);
            },
        );

        self::assertNull($stream->next(), 'the vanished row degrades to null');
        self::assertNotNull($stream->next(), 'the next live key still flows');
    }

    // ── the onion is unchanged ──────────────────────────────────────────

    public function test_add_filter_returns_self_for_chaining(): void
    {
        $stream = new AccountUpdateStream(7);
        $filter = new class implements RelayFilter
        {
            public function handle(RelayMessage $message, callable $next): RelayMessage
            {
                return $next($message);
            }
        };

        self::assertSame($stream, $stream->addFilter($filter));
    }

    public function test_registered_filter_can_rewrite_params_before_the_publisher(): void
    {
        $published = [];
        $stream = new AccountUpdateStream(
            7,
            self::reader([self::key(9, 0)]),
            self::hydrator(['constructor' => 'updateNewMessage', 'secret' => 'leak']),
            static function (RelayMessage $message) use (&$published): void {
                $published[] = $message;
            },
        );
        $stream->addFilter(new class implements RelayFilter
        {
            public function handle(RelayMessage $message, callable $next): RelayMessage
            {
                $params = $message->params;
                unset($params['secret']);

                return $next($message->withParams($params));
            }
        });

        $message = $stream->next();

        self::assertNotNull($message);
        self::assertArrayNotHasKey('secret', $message->params);
        self::assertSame(['account_id' => 7, 'seq' => 9, 'position' => 0, 'constructor' => 'updateNewMessage'], $message->params);
        self::assertCount(1, $published);
        self::assertSame($message->params, $published[0]->params);
    }

    public function test_registered_filter_can_drop_an_update_before_the_publisher(): void
    {
        $published = [];
        $stream = new AccountUpdateStream(
            7,
            self::reader([self::key(9, 0)]),
            self::hydrator(['constructor' => 'updateNewMessage']),
            static function (RelayMessage $message) use (&$published): void {
                $published[] = $message;
            },
        );
        $stream->addFilter(new class implements RelayFilter
        {
            public function handle(RelayMessage $message, callable $next): RelayMessage
            {
                return new RelayMessage(RelayMessage::INBOUND, 'dropped', []);
            }
        });

        $message = $stream->next();

        self::assertNotNull($message);
        self::assertSame('dropped', $message->method, 'control seam: data dropped before it left the stream');
        self::assertCount(1, $published);
        self::assertSame('dropped', $published[0]->method);
        self::assertSame([], $published[0]->params);
    }

    // ── helpers ─────────────────────────────────────────────────────────

    /**
     * The v2 bus entry's committed key — the ONLY shape the reader may yield.
     *
     * @return array{account_id: int, seq: int, position: int}
     */
    private static function key(int $seq, int $position): array
    {
        return ['account_id' => 7, 'seq' => $seq, 'position' => $position];
    }

    /**
     * @param  list<array<string, mixed>>  $keys
     * @return callable(): (array<string, mixed>|null)
     */
    private static function reader(array $keys): callable
    {
        return static function () use (&$keys): ?array {
            return $keys === [] ? null : array_shift($keys);
        };
    }

    /**
     * A hydrator that always resolves, with the row columns a test cares about.
     *
     * @param  array<string, mixed>  $attributes
     * @return callable(array<string, mixed>): ?Model
     */
    private static function hydrator(array $attributes = []): callable
    {
        return static fn (array $key): ?Model => self::row($attributes);
    }

    /**
     * The committed row, as a real Eloquent model — the class requires a Model
     * and nothing more, so the model is anonymous and table-less.
     *
     * @param  array<string, mixed>  $attributes
     */
    private static function row(array $attributes): Model
    {
        $model = new class extends Model
        {
            protected $guarded = [];
        };

        $model->setRawAttributes($attributes);

        return $model;
    }

    /**
     * @param  list<string>  $calls
     * @return callable(mixed...): void
     */
    private static function recorder(array &$calls, string $label): callable
    {
        return static function (mixed ...$args) use (&$calls, $label): void {
            $calls[] = $label.'('.implode(',', $args).')';
        };
    }
}
