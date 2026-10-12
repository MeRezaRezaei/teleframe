<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Bus\IngestConsumer;
use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Bus\RouteTable;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\HandlerSink;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Laravel\AppServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Teleclient;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;

/**
 * BREAK 2 — the one live intake path delivered an EMPTY constructor, and
 * the routed branch delivered NOTHING.
 *
 * 1. `Update::fromMirror()` built `new self([], …)`, so `constructor()` was
 *    '' and only the `*` catch-all matcher could ever fire — an
 *    `AppTrigger::on('updateNewMessage', …)` was unreachable. The mirror
 *    rows DO carry the constructor name in a `constructor` column, so the
 *    key was recoverable all along.
 * 2. DaemonCommand passed `onRouted: null` into IngestConsumer, so when a
 *    host DID configure a bus route, the entry was re-published to the
 *    target stream and never reached the handler pipeline.
 */
final class AppReachabilityIntakeTest extends TestCase
{
    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            TeleframeServiceProvider::class,
            AppServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        HandlerRegistry::reset();
    }

    protected function tearDown(): void
    {
        HandlerRegistry::reset();

        parent::tearDown();
    }

    public function test_a_mirrored_update_carries_the_constructor_a_matcher_needs(): void
    {
        $update = Update::fromMirror(
            ReachabilityMessage::reachabilityMessage(42, 7, 'ping'),
            42,
        );

        self::assertSame('updateNewMessage', $update->constructor());
        self::assertSame('event', $update->source);
        self::assertNotNull($update->model);
    }

    public function test_the_mirror_constructor_survives_the_round_trip_helpers(): void
    {
        $update = Update::fromMirror(ReachabilityMessage::reachabilityMessage(42, 7, 'ping'), 42)
            ->withSelfOriginated(true)
            ->withModel(null);

        self::assertSame('updateNewMessage', $update->constructor());
    }

    /**
     * The mirror row has no `constructor` attribute at all (the legacy Tl*
     * models) — the key degrades to '' and the catch-all still matches.
     * Nothing regresses for the old shape.
     */
    public function test_a_model_without_a_constructor_column_degrades_to_the_catch_all(): void
    {
        $model = new class extends Model
        {
            protected $table = 'anchor';

            protected $guarded = [];
        };
        $model->setAttribute('id', 3);

        $update = Update::fromMirror($model, 9);

        self::assertSame('', $update->constructor());
    }

    /**
     * A now-ROUTED entry must still reach the handler pipeline, wired the
     * way DaemonCommand wires it. Before the fix the daemon passed
     * `onRouted: null` and this array stayed empty.
     */
    public function test_a_routed_bus_entry_reaches_the_handler_pipeline(): void
    {
        $fired = [];
        $this->app->make(HandlerRegistry::class)->on(
            'updateNewMessage',
            static function (Update $u) use (&$fired): void {
                $fired[] = $u->accountId;
            },
        );

        $redis = new ArrayRedis;
        (new RouteTable($redis))->set('updateNewMessage*', 'tg:target:messages');

        (new RedisStreamSink($redis, 42))->handle([
            '_' => 'updateNewMessage',
            'message' => ['_' => 'message', 'id' => 7, 'message' => 'ping'],
        ], '42');

        $sink = new HandlerSink($this->app->make(UpdateDispatcher::class));
        $consumer = new IngestConsumer(
            $redis,
            $this->app->make(Teleclient::class),
            null,
            static fn (array $update, int $accountId): bool => $sink->handle($update, (string) $accountId),
        );

        $stats = $consumer->consumeOnce();

        self::assertSame(1, $stats['forwarded']);
        self::assertSame([42], $fired, 'the routed entry was delivered to the pipeline, not just re-published');
    }
}
