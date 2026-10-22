<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppResponder;
use MeRezaRezaei\Teleframe\App\AppTrigger;
use MeRezaRezaei\Teleframe\Handler\HandlerMatcher;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Update;
use PHPUnit\Framework\TestCase;

/**
 * AppResponder (design §2/§4 / gap G3) — the app→pipeline registration
 * bridge: one install() turns an AppDefinition's trigger list into HOT
 * registrations on the SHIPPED HandlerRegistry grammar under the identity
 * tag `app:{name}`. uninstall() works in the §11 registry-snapshot posture:
 * it COUNTS the `app:{name}`-tagged rows in the live table — the shared
 * table itself is append-only by design (the shipped HandlerRegistry has no
 * removal API and is deliberately untouched), so the row count never drops.
 */
final class AppResponderTest extends TestCase
{
    private HandlerRegistry $table;

    private AppResponder $responder;

    protected function setUp(): void
    {
        $this->table = new HandlerRegistry;
        $this->responder = new AppResponder($this->table);
    }

    private static function handler(): \Closure
    {
        return static function (Update $u): void {};
    }

    private function app(string $name = 'echo-bot', int $triggers = 2): AppDefinition
    {
        $rows = [];
        for ($i = 0; $i < $triggers; $i++) {
            $rows[] = AppTrigger::on('updateNew'.ucfirst($name).$i, self::handler(), priority: $i);
        }

        return AppDefinition::define($name, AppMode::Bot, $rows);
    }

    public function test_install_registers_every_trigger_with_tag_and_returns_the_count(): void
    {
        $installed = $this->responder->install($this->app());

        self::assertSame(2, $installed);
        self::assertSame(2, $this->table->count());

        $rows = $this->table->compile();
        // compile() sorts priority-descending: the priority-1 row leads.
        self::assertSame('updateNewEcho-bot1', $rows[0]->match);
        self::assertSame('updateNewEcho-bot0', $rows[1]->match);
        self::assertSame('app:echo-bot', $rows[0]->tag);
        self::assertSame('app:echo-bot', $rows[1]->tag);
    }

    public function test_install_priorities_and_on_own_pass_through_untouched(): void
    {
        $app = AppDefinition::define('ping-bot', AppMode::Bot, [
            AppTrigger::on('*', self::handler(), priority: 9, onOwn: true),
        ]);

        $this->responder->install($app);

        $row = $this->table->compile()[0];
        self::assertSame(9, $row->priority);
        self::assertTrue($row->onOwn);
        self::assertSame('app:ping-bot', $row->tag);
    }

    public function test_handler_matcher_sees_the_installed_trigger(): void
    {
        $closure = self::handler();
        $app = AppDefinition::define('echo-bot', AppMode::Bot, [
            AppTrigger::on('updateNewMessage', $closure),
        ]);

        $this->responder->install($app);

        $matcher = new HandlerMatcher($this->table);
        $match = $matcher->match('updateNewMessage');

        self::assertNotNull($match);
        self::assertSame($closure, $match->handler->handler);
        self::assertSame('app:echo-bot', $match->handler->tag);
        self::assertNull($matcher->match('updateNewChannel'), 'unregistered constructors do not match');
    }

    public function test_uninstall_counts_only_this_apps_tagged_rows_and_never_removes_rows(): void
    {
        // A foreign tagged row sits in the table before the app installs:
        // uninstall must count ONLY the app:echo-bot rows, never others.
        //
        // The foreign row is a CATCH-ALL at priority -1, not 0: a catch-all that
        // PRECEDES an app trigger at the same priority makes that trigger
        // unreachable, and install() refuses it by name (F16-1 — the refusal is
        // pinned in AppResponderTriggerShadowingTest). A foreign catch-all one
        // step BELOW is the honest shape for this test, whose subject is the
        // tagged-row count and not the ordering.
        $this->table->on('*', self::handler(), -1, tag: 'watchdog');

        $this->responder->install($this->app());

        self::assertSame(3, $this->table->count());

        $removed = $this->responder->uninstall('echo-bot');

        // The count IS the app's footprint on the pipeline...
        self::assertSame(2, $removed);
        // ...but the shipped table is append-only — nothing gets removed.
        self::assertSame(3, $this->table->count(), 'the shared table is append-only — uninstall never drops rows');

        // A name with no tagged rows counts zero.
        self::assertSame(0, $this->responder->uninstall('ghost-bot'));
        self::assertSame(3, $this->table->count());
    }

    public function test_responder_defaults_to_the_shared_hot_table(): void
    {
        HandlerRegistry::reset();

        try {
            $shared = HandlerRegistry::instance();
            $responder = new AppResponder;

            self::assertSame(2, $responder->install($this->app()));
            self::assertSame(0, $responder->install($this->app('second', 0)));
            self::assertSame(2, $shared->count());
            self::assertSame(2, $responder->uninstall('echo-bot'));
            self::assertSame(2, $shared->count(), 'default table is the shared append-only hot table');
        } finally {
            HandlerRegistry::reset();
        }
    }
}
