<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppCallSpec;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppRegistry;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\App\Scheduler\TemporalAppScheduler;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;
use PHPUnit\Framework\TestCase;

final class TemporalAppSchedulerTest extends TestCase
{
    public function test_install_passes_spec_payload_through_and_returns_status_verbatim(): void
    {
        $publisher = new class implements SchedulePublisher
        {
            public ?array $installed = null;

            public function install(array $schedules): array
            {
                $this->installed = $schedules;

                return ['app-call-echo-bot-status-ping-abc' => 'installed'];
            }

            public function describe(): string
            {
                return 'fake';
            }
        };

        $app = AppDefinition::define('echo-bot', AppMode::Bot, calls: [
            AppCall::every('status-ping', 3600, 'messages.sendMessage', ['peer' => '@ops']),
        ]);

        $scheduler = new TemporalAppScheduler($publisher, $app);
        $status = $scheduler->install();

        $spec = new AppCallSpec;
        self::assertSame($spec->installSpec($app), $publisher->installed);
        self::assertSame(['app-call-echo-bot-status-ping-abc' => 'installed'], $status);
    }

    public function test_install_with_no_constructor_source_accepts_app_argument(): void
    {
        $publisher = new class implements SchedulePublisher
        {
            public ?array $installed = null;

            public function install(array $schedules): array
            {
                $this->installed = $schedules;

                return $schedules === [] ? [] : ['x' => 'skipped (null schedule driver)'];
            }

            public function describe(): string
            {
                return 'fake';
            }
        };

        $app = AppDefinition::define('echo-bot', AppMode::Bot, calls: [
            AppCall::every('status-ping', 3600, 'messages.sendMessage'),
        ]);

        $scheduler = new TemporalAppScheduler($publisher);
        $status = $scheduler->install($app);

        self::assertArrayHasKey((new AppCallSpec)->callId($app, $app->calls()[0]), $publisher->installed);
    }

    public function test_install_from_runtime(): void
    {
        $publisher = new class implements SchedulePublisher
        {
            public ?array $installed = null;

            public function install(array $schedules): array
            {
                $this->installed = $schedules;

                return [];
            }

            public function describe(): string
            {
                return 'fake';
            }
        };

        $app = AppDefinition::define('echo-bot', AppMode::User, calls: [
            AppCall::every('status-ping', 1800, 'messages.sendMessage'),
        ]);
        $runtime = new AppRuntime(new AppRegistry, new NullSchedulePublisher);
        $runtime->register($app);

        $scheduler = new TemporalAppScheduler($publisher, $runtime);
        $scheduler->install();

        self::assertNotEmpty($publisher->installed);
    }

    public function test_describe_delegates(): void
    {
        $publisher = new class implements SchedulePublisher
        {
            public function install(array $schedules): array
            {
                return [];
            }

            public function describe(): string
            {
                return 'temporal live';
            }
        };

        $scheduler = new TemporalAppScheduler($publisher);
        self::assertSame('temporal live', $scheduler->describe());
    }
}
