<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppException;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppRegistry;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\App\AppTrigger;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Laravel\AppServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\Facades\Teleframe;
use MeRezaRezaei\Teleframe\Laravel\Facades\TelegramApp;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * BREAK 3 — the promised facade could not do the promised thing.
 *
 * Its own docblock was fatal: it advertised `TelegramApp::service()` (no such
 * method on AppRuntime, and Facade has no __call magic of its own) and told
 * the reader to feed `TelegramApp::list()`'s array into `register()`, whose
 * parameter is an AppDefinition. The documented `Teleframe::app()` alias
 * pointed at TeleframeClient, which has no app() at all.
 *
 * This test checks the docblocks MECHANICALLY — every `@method` line and
 * every `Facade::method(` call site in the class docblock is resolved against
 * the real accessor target and must exist. A facade whose own examples fatal
 * is worse than no facade.
 */
final class TelegramAppFacadeContractTest extends TestCase
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

    /**
     * Every `@method static … name(…)` line in the TelegramApp docblock
     * names a method the accessor target really has.
     */
    public function test_every_docblock_method_annotation_resolves_on_the_accessor_target(): void
    {
        $target = new \ReflectionClass($this->accessorTarget());

        $annotated = $this->annotatedMethods(TelegramApp::class);
        self::assertNotEmpty($annotated, 'the facade still documents its surface (@method lines)');

        foreach ($annotated as $method) {
            self::assertTrue(
                $target->hasMethod($method),
                "TelegramApp's @method docblock advertises [{$method}], which {$target->getName()} does not have",
            );
        }
    }

    /**
     * Every call site inside the facade's own docblock examples resolves.
     * This is the "read them by eye is not a test" rule: `TelegramApp::x(`
     * in the docblock MUST be a real method on the accessor target.
     */
    public function test_every_docblock_example_call_resolves_on_the_accessor_target(): void
    {
        $target = new \ReflectionClass($this->accessorTarget());

        $called = $this->docblockCallSites(TelegramApp::class, 'TelegramApp');
        self::assertNotEmpty($called, 'the facade still ships worked examples');

        foreach ($called as $method) {
            self::assertTrue(
                $target->hasMethod($method),
                "TelegramApp's docblock example calls TelegramApp::{$method}(), which {$target->getName()} does not have",
            );
        }
    }

    /** The docblock example that fed list()'s array into register(). */
    public function test_register_takes_an_app_definition_not_a_registry_array(): void
    {
        $parameter = (new \ReflectionMethod(AppRuntime::class, 'register'))->getParameters()[0];

        self::assertSame(
            AppDefinition::class,
            (string) $parameter->getType(),
        );
    }

    /**
     * The same mechanical rule for the main Teleframe facade: every method
     * its docblock advertises resolves EITHER to a real static on the facade
     * itself (the module surface) OR to a real method on its accessor target
     * TeleframeClient (the wire surface). Before the fix `app()` was
     * advertised and resolved to neither — it was the documented one-liner
     * for the entire app framework and it was dead.
     */
    public function test_every_teleframe_facade_docblock_method_resolves(): void
    {
        $facade = new \ReflectionClass(Teleframe::class);
        $target = new \ReflectionClass(TeleframeClient::class);

        $annotated = $this->annotatedMethods($facade->getName());
        self::assertNotEmpty($annotated);

        foreach ($annotated as $method) {
            self::assertTrue(
                $facade->hasMethod($method) || $target->hasMethod($method),
                "the Teleframe facade's @method docblock advertises [{$method}], which is neither"
                .' a real static on the facade nor a method on its accessor target '.$target->getName(),
            );
        }
    }

    /** The documented alias returns the SAME runtime, not a second copy. */
    public function test_the_documented_alias_returns_the_same_runtime_instance(): void
    {
        self::assertSame(
            $this->app->make('teleclient.app.service'),
            Teleframe::app(),
            'the alias and the facade share ONE AppRuntime',
        );
    }

    /** The facade reads messages. */
    public function test_the_facade_can_read_messages(): void
    {
        $read = [];
        $runtime = $this->runtimeWithTransport(
            static function (AppMode $mode) use (&$read): object {
                return new class($read)
                {
                    /** @param list<array{string, array<string, mixed>}> $read */
                    public function __construct(public array &$read) {}

                    public function getHistory(int|string|array $peer, int $limit = 50, int $offsetId = 0): array
                    {
                        $this->read[] = ['messages.getHistory', ['peer' => $peer, 'limit' => $limit]];

                        return ['messages' => [['id' => 5, 'message' => 'ping']]];
                    }
                };
            },
        );

        $messages = $runtime->read('@ops', 10);

        self::assertSame([['messages.getHistory', ['peer' => '@ops', 'limit' => 10]]], $read);
        self::assertSame([['id' => 5, 'message' => 'ping']], $messages);
    }

    /** The facade answers them. */
    public function test_the_facade_can_answer_a_message(): void
    {
        $sent = [];
        $runtime = $this->runtimeWithTransport(
            static function (AppMode $mode) use (&$sent): object {
                return new class($sent)
                {
                    /** @param list<array{string, array<string, mixed>}> $sent */
                    public function __construct(public array &$sent) {}

                    public function sendMessage(int|string|array $peer, string $text, array $options = []): array
                    {
                        $this->sent[] = ['messages.sendMessage', ['peer' => $peer, 'message' => $text]];

                        return ['id' => 99];
                    }
                };
            },
        );

        $result = $runtime->answer(42, '@ops', 'pong');

        self::assertSame('messages.sendMessage', $sent[0][0]);
        self::assertSame('@ops', $sent[0][1]['peer']);
        self::assertSame('pong', $sent[0][1]['message']);
        self::assertSame(['id' => 99], $result);
    }

    /** Bot mode cannot read history — the Bot API has no such method. */
    public function test_reading_from_a_bot_transport_refuses_loud(): void
    {
        $runtime = $this->runtimeWithTransport(static fn (AppMode $mode): object => new class
        {
            public function sendMessage(int|string|array $peer, string $text, array $options = []): array
            {
                return [];
            }
        });

        $this->expectException(AppException::class);
        $runtime->read('@ops', 5, AppMode::Bot);
    }

    /** The docblock's `define()` sugar declares AND installs in one call. */
    public function test_define_declares_and_installs_in_one_call(): void
    {
        $seen = [];
        $runtime = $this->runtimeWithTransport(static fn (AppMode $mode): object => new class
        {
            public function sendMessage(int|string|array $peer, string $text, array $options = []): array
            {
                return [];
            }
        });

        $name = $runtime->define('sugar-bot', AppMode::User, [
            AppTrigger::on('updateNewMessage', static function (Update $u) use (&$seen): void {
                $seen[] = $u->accountId;
            }),
        ], [
            AppCall::every('status-ping', 3600, 'messages.sendMessage', ['peer' => '@ops', 'message' => 'alive']),
        ]);

        self::assertSame('sugar-bot', $name);
        self::assertCount(1, $runtime->registry()->resolve('sugar-bot')->triggers());
        self::assertSame(1, HandlerRegistry::instance()->count(), 'the trigger is live immediately');

        self::assertNotNull($runtime->apps());

        $callIds = array_keys($runtime->calls());
        self::assertCount(
            1,
            $callIds,
            'one declared call arms one schedule',
        );
        self::assertStringStartsWith('app-call-sugar-bot-status-ping-', $callIds[0]);
    }

    /**
     * @return class-string the class the facade's accessor resolves to
     */
    private function accessorTarget(): string
    {
        $root = TelegramApp::getFacadeRoot();
        self::assertInstanceOf(AppRuntime::class, $root);

        return AppRuntime::class;
    }

    /**
     * The `name(` part of every `@method … name(` annotation in a class
     * docblock.
     *
     * @return list<string>
     */
    private function annotatedMethods(string $class): array
    {
        $doc = (new \ReflectionClass($class))->getDocComment();
        self::assertIsString($doc);

        $found = [];
        if (preg_match_all('/@method\s+static\s+[^\s(]+\s+(\w+)\s*\(/', $doc, $m) > 0) {
            $found = $m[1];
        }

        return array_values(array_unique($found));
    }

    /**
     * Every `Facade::method(` call site in a class docblock.
     *
     * @return list<string>
     */
    private function docblockCallSites(string $class, string $facade): array
    {
        $doc = (new \ReflectionClass($class))->getDocComment();
        self::assertIsString($doc);

        $found = [];
        if (preg_match_all('/'.preg_quote($facade, '/').'::(\w+)\s*\(/', $doc, $m) > 0) {
            $found = $m[1];
        }

        return array_values(array_unique($found));
    }

    private function runtimeWithTransport(callable $transport): AppRuntime
    {
        return new AppRuntime(
            new AppRegistry,
            new NullSchedulePublisher,
            $transport,
        );
    }
}
