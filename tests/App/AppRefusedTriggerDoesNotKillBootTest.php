<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\Handler\HandlerMatcher;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Laravel\AppServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\Console\AppCommand;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * F16-5 — ONE UNREACHABLE TRIGGER IN CONFIG MUST NOT TAKE DOWN THE PROCESS.
 *
 * `AppServiceProvider::installTriggers()` installed every configured app in a
 * bare loop with no guard, and `AppResponder::install()` throws
 * InvalidArgumentException when a trigger could never win the first-match
 * table. A `teleframe.app.apps` row whose trigger is refused therefore threw
 * out of provider `boot()` — which takes down EVERY Laravel process, artisan
 * included, before any command can run or say why.
 *
 * That blast radius was already total for the identical-string case the shipped
 * guard caught. F16-1 widens the guard to the overlapping-grammar class (a host
 * `onMessage()` catch-all, a `prefix*`, a higher-priority superset), which are
 * ORDINARY configs — so leaving boot() unguarded would have converted silent
 * wrong into process death. The refusal is therefore REPORTED: recorded on the
 * responder, printed by `teleframe:app list`, and the apps that CAN run still
 * run.
 *
 * The refusal is never swallowed: it exits non-zero and names the app and the
 * trigger.
 */
final class AppRefusedTriggerDoesNotKillBootTest extends TestCase
{
    /**
     * Two config rows, the ordinary way a host reaches the refused case: a
     * first app claims `updateNewMessage`, a second app claims the same update
     * at the same default priority. The second can never win the first-match
     * table, so it must be refused BY NAME rather than installed dead.
     *
     * @return list<array<string, mixed>>
     */
    private static function apps(): array
    {
        return [
            [
                'name' => 'first-reader',
                'mode' => 'user',
                'triggers' => [
                    [
                        'match' => 'updateNewMessage',
                        'handler' => static function (Update $u): void {},
                    ],
                ],
                'calls' => [],
            ],
            [
                'name' => 'second-reader',
                'mode' => 'user',
                'triggers' => [
                    [
                        'match' => 'updateNewMessage',
                        'handler' => static function (Update $u): void {},
                    ],
                ],
                'calls' => [],
            ],
        ];
    }

    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            TeleframeServiceProvider::class,
            AppServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('teleframe.app.apps', self::apps());
    }

    protected function setUp(): void
    {
        HandlerRegistry::reset();

        parent::setUp();

        $this->app->make(Kernel::class)->registerCommand(new AppCommand);
    }

    protected function tearDown(): void
    {
        HandlerRegistry::reset();

        parent::tearDown();
    }

    /**
     * The headline: the process is ALIVE. Reaching this assertion at all is the
     * claim — the boot happened with a refused row in config, and Testbench
     * built a working container out of it.
     */
    public function test_a_refused_trigger_does_not_throw_out_of_provider_boot(): void
    {
        $runtime = $this->app->make('teleclient.app.service');

        self::assertInstanceOf(AppRuntime::class, $runtime, 'the process booted');
        self::assertTrue(
            $this->app->make('teleclient.app.registry')->has('first-reader'),
            'the app that CAN run is still defined',
        );
        self::assertTrue(
            $this->app->make('teleclient.app.registry')->has('second-reader'),
            'the refused app is still DEFINED — it is refused, not dropped',
        );
    }

    /** The refusal is visible, by name, through `teleframe:app list`. */
    public function test_teleframe_app_list_reports_the_refusal_and_exits_non_zero(): void
    {
        $exit = Artisan::call('teleframe:app', ['action' => 'list']);
        $out = Artisan::output(); // output() drains — capture once

        self::assertSame(AppCommand::FAILURE, $exit, 'a refused app is not a green command');
        self::assertStringContainsString('1 app(s) refused', $out);
        self::assertStringContainsString('app [second-reader] not installed', $out);
        self::assertStringContainsString('trigger [updateNewMessage] at priority 0 is unreachable', $out);
        self::assertStringContainsString('first-reader', $out, 'the healthy app is still listed');
        self::assertStringContainsString('installed', $out);
        self::assertStringContainsString('REFUSED', $out, 'the unreachable app says so in the table, not only in the log');
    }

    /** The healthy app still fires; the refused one left no row on the hot table. */
    public function test_the_apps_that_can_run_still_run(): void
    {
        $table = $this->app->make(HandlerRegistry::class);

        self::assertSame(1, $table->count(), 'only the reachable app is on the table');

        $tags = array_map(static fn ($row): ?string => $row->tag, $table->all());
        self::assertSame(['app:first-reader'], $tags);

        $match = (new HandlerMatcher($table))->match('updateNewMessage');
        self::assertNotNull($match);
        self::assertSame('app:first-reader', $match->handler->tag, 'the reachable trigger is the one that dispatches');
    }

    /** `teleframe:app register` reports the same refusal and does not abort the run. */
    public function test_register_reports_the_refusal_instead_of_dying_on_it(): void
    {
        $exit = Artisan::call('teleframe:app', ['action' => 'register']);
        $out = Artisan::output(); // output() drains — capture once

        self::assertSame(AppCommand::FAILURE, $exit);
        self::assertStringContainsString('app(s) refused', $out);
        self::assertStringContainsString('app [second-reader] not installed', $out);
        self::assertStringContainsString('Registered 1 app(s): first-reader', $out,
            'the app that CAN run is still registered — one bad row does not hide it');
    }
}
