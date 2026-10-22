<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Teleframe\Control;

use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Control\AccountRelay;
use MeRezaRezaei\Teleframe\Control\AccountUpdateStream;
use MeRezaRezaei\Teleframe\Control\RelayContext;
use MeRezaRezaei\Teleframe\Control\RelayFilter;
use MeRezaRezaei\Teleframe\Control\RelayMessage;
use MeRezaRezaei\Teleframe\Control\RelayTerminal;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The control-layer transport terminal: OUTBOUND messages are dispatched to
 * the bound account client (whose scope is resolved lazily on the FIRST
 * outbound, never at construction), the RPC reply returns as the produced
 * INBOUND RelayMessage, and BOTH directions pass through the same
 * RelayFilter onion — outbound via AccountRelay::send(), inbound via
 * AccountUpdateStream::next(). That inbound leg carries the COMMITTED
 * IDENTITY ROW (D-044: the reader yields a key, the hydrator resolves it to
 * the model), never a decoded update array. Pure plain-PHP: fake client /
 * fake resolver only, no network, no credentials.
 */
final class RelayTerminalTest extends TestCase
{
    public function test_construction_never_resolves_scope_and_handles_noop_defaults(): void
    {
        $resolved = 0;
        $terminal = new RelayTerminal(function () use (&$resolved): object {
            $resolved++;

            return FakeTransportClient::scope();
        });

        self::assertFalse($terminal->resolved());
        self::assertSame(0, $resolved, 'constructing the terminal must not dial / resolve');
    }

    public function test_inbound_passthrough_resolves_nothing(): void
    {
        $resolved = 0;
        $terminal = new RelayTerminal(function () use (&$resolved): object {
            $resolved++;

            return FakeTransportClient::scope();
        });

        $message = new RelayMessage(RelayMessage::INBOUND, 'update', ['_' => 'updateNewMessage']);

        self::assertSame($message, $terminal($message), 'inbound belongs to the update stream, not the terminal');
        self::assertSame(0, $resolved, 'inbound must never dial the bound client');
    }

    public function test_outbound_resolves_scope_lazily_once_and_reuses_it(): void
    {
        $resolved = 0;
        $scope = FakeTransportClient::scope();
        $terminal = new RelayTerminal(function () use (&$resolved, $scope): object {
            $resolved++;

            return $scope;
        }, static fn (mixed $seen, RelayMessage $m): RelayMessage => $m->replace(
            new RelayMessage(RelayMessage::INBOUND, $m->method, ['ok' => true]),
        ));

        $outbound = new RelayMessage(RelayMessage::OUTBOUND, 'messages.getDialogs', []);
        $terminal($outbound);
        $terminal($outbound);

        self::assertSame(1, $resolved, 'scope is re-dialed/fresh only once — the terminal binds on first outbound');
        self::assertTrue($terminal->resolved());
    }

    public function test_default_executor_dispatches_to_the_bound_client_and_returns_the_produced_reply(): void
    {
        $client = new FakeTransportClient;
        $terminal = new RelayTerminal(static fn (): object => (object) ['client' => $client]);

        $reply = $terminal(new RelayMessage(RelayMessage::OUTBOUND, 'messages.sendMessage', ['peer' => 42]));

        self::assertSame([['messages.sendMessage', ['peer' => 42]]], $client->calls);
        self::assertInstanceOf(RelayMessage::class, $reply);
        self::assertSame(RelayMessage::INBOUND, $reply->direction, 'the RPC reply flows back to the client');
        self::assertSame('messages.sendMessage', $reply->method);
        self::assertSame(['_' => 'messages.sendMessage', 'ok' => true], $reply->params);
    }

    public function test_default_executor_accepts_a_raw_client_scope(): void
    {
        $client = new FakeTransportClient;
        $terminal = new RelayTerminal(static fn (): object => $client);

        $reply = $terminal(new RelayMessage(RelayMessage::OUTBOUND, 'messages.getDialogs', ['limit' => 5]));

        self::assertSame([['messages.getDialogs', ['limit' => 5]]], $client->calls);
        self::assertSame(['_' => 'messages.getDialogs', 'ok' => true], $reply->params);
    }

    public function test_injected_executor_sees_the_same_message_and_scope(): void
    {
        $seen = [];
        $client = new FakeTransportClient;
        $scope = (object) ['client' => $client];
        $terminal = new RelayTerminal(
            static fn (): object => $scope,
            function (mixed $scope, RelayMessage $message) use (&$seen): RelayMessage {
                $seen['scope'] = $scope;
                $seen['method'] = $message->method;

                return new RelayMessage(RelayMessage::INBOUND, 'replied', ['n' => count($message->params)]);
            },
        );

        $reply = $terminal(new RelayMessage(RelayMessage::OUTBOUND, 'messages.getDialogs', ['limit' => 9]));

        self::assertSame(['scope' => $scope, 'method' => 'messages.getDialogs'], $seen);
        self::assertSame('replied', $reply->method);
        self::assertSame([], $client->calls, 'an injected executor fully replaces the default dispatch');
    }

    public function test_filter_rewrites_params_before_the_terminal_dispatches(): void
    {
        $client = new FakeTransportClient;
        $terminal = new RelayTerminal(static fn (): object => (object) ['client' => $client]);
        $relay = new AccountRelay(
            new RelayContext(1, 2, 'mine'),
            static fn (): object => (object) ['client' => $client],
        );
        $relay->push(new class implements RelayFilter
        {
            public function handle(RelayMessage $message, callable $next): RelayMessage
            {
                return $next($message->withParams(['limit' => 100]));
            }
        });

        $reply = $relay->send(
            new RelayMessage(RelayMessage::OUTBOUND, 'messages.search', []),
            $terminal,
        );

        self::assertSame([['messages.search', ['limit' => 100]]], $client->calls, 'the terminal dispatches the REWRITTEN params');
        self::assertSame(RelayMessage::INBOUND, $reply->direction);
        self::assertSame(['_' => 'messages.search', 'ok' => true], $reply->params);
    }

    public function test_filter_can_drop_outbound_before_the_terminal_ever_dials(): void
    {
        $client = new FakeTransportClient;
        $terminal = new RelayTerminal(static fn (): object => (object) ['client' => $client]);
        $relay = new AccountRelay(
            new RelayContext(1, 2, 'mine'),
            static fn (): object => (object) ['client' => $client],
        );
        $relay->push(new class implements RelayFilter
        {
            public function handle(RelayMessage $message, callable $next): RelayMessage
            {
                if ($message->method === 'account.getPassword') {
                    return new RelayMessage(RelayMessage::OUTBOUND, 'denied', []);
                }

                return $next($message);
            }
        });

        $result = $relay->send(
            new RelayMessage(RelayMessage::OUTBOUND, 'account.getPassword', []),
            $terminal,
        );

        self::assertSame('denied', $result->method, 'short-circuited before the terminal — data never leaves');
        self::assertSame([], $client->calls, 'a dropped outbound must never reach the bound client');
        self::assertFalse($terminal->resolved(), 'and so the scope is never resolved/dialed');
    }

    public function test_outbound_without_a_scope_resolver_throws_without_credentials(): void
    {
        $terminal = new RelayTerminal;

        try {
            $terminal(new RelayMessage(RelayMessage::OUTBOUND, 'messages.getDialogs', []));
            self::fail('expected RuntimeException');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('no scope resolver', $e->getMessage());
        }
    }

    public function test_both_directions_pass_through_the_same_filter_onion(): void
    {
        $seen = [];
        $shared = $this->directionCollector($seen);
        $client = new FakeTransportClient;

        $terminal = new RelayTerminal(static fn (): object => (object) ['client' => $client]);
        $relay = new AccountRelay(
            new RelayContext(1, 2, 'mine'),
            static fn (): object => (object) ['client' => $client],
        );
        $relay->push($shared);

        $reply = $relay->send(
            new RelayMessage(RelayMessage::OUTBOUND, 'messages.getDialogs', ['limit' => 10]),
            $terminal,
        );

        $keys = [['account_id' => 1, 'seq' => 9, 'position' => 0]];
        $stream = new AccountUpdateStream(
            accountId: 1,
            reader: static function () use (&$keys): ?array {
                return array_shift($keys);
            },
            hydrate: static function (array $key): ?Model {
                $row = new class extends Model
                {
                    protected $guarded = [];
                };
                $row->setRawAttributes(['constructor' => 'updateNewMessage']);

                return $row;
            },
            filters: [$shared],
        );
        $update = $stream->next();

        self::assertSame(
            [
                'before:outbound:messages.getDialogs',
                'after:inbound:messages.getDialogs',
                'before:inbound:update',
                'after:inbound:update',
            ],
            $seen,
            'one filter instance sees the whole conversation: the outbound leg in, the produced reply leg out of the terminal, then the inbound update leg',
        );
        self::assertSame(RelayMessage::INBOUND, $reply->direction, 'produced reply flows back inbound');
        self::assertInstanceOf(RelayMessage::class, $update);
        self::assertSame('update', $update->method);
    }

    /**
     * @param  list<string>  $seen
     */
    private function directionCollector(array &$seen): RelayFilter
    {
        return new class($seen) implements RelayFilter
        {
            /** @param list<string> $seen */
            public function __construct(private array &$seen) {}

            public function handle(RelayMessage $message, callable $next): RelayMessage
            {
                $this->seen[] = 'before:'.$message->direction.':'.$message->method;
                $result = $next($message);
                $this->seen[] = 'after:'.$result->direction.':'.$result->method;

                return $result;
            }
        };
    }
}

/**
 * Fake user-client: records every call() the default executor makes and
 * answers with a canned RPC result. No network, no session string anywhere.
 */
final class FakeTransportClient
{
    /** @var list<array{0: string, 1: array<string, mixed>}> */
    public array $calls = [];

    public static function scope(): object
    {
        return (object) ['client' => new self];
    }

    /** @param array<string, mixed> $params */
    public function call(string $method, array $params = []): array
    {
        $this->calls[] = [$method, $params];

        return ['_' => $method, 'ok' => true];
    }
}
