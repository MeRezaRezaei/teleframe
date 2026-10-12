<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use MeRezaRezaei\Teleframe\App\AppCallSpec;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\App\AppSupport;
use RuntimeException;

/**
 * artisan teleframe:app (design 2026-09-24 §2/§7): the CLI shell over
 * AppRuntime — the bot/user app framework surface.
 *
 * Actions:
 *   register  — re-register every defined app onto the SHIPPED handler
 *               pipeline (hot: the shared table is read live at dispatch)
 *   list      — registry rows (name, mode, trigger count, call count)
 *   calls     — installSpec preview for every registered app
 *               (--install: probe-install through the schedule seam)
 *   run       — invoke ONE scheduled call now (offline probe, no schedule
 *               host required): --app=NAME --call=CALL
 *
 * Every dependency comes from the container through AppSupport::SERVICE_KEY
 * (mirrors TstoreCommand/NotifyCommand seams): the provider default builds
 * AppRuntime from teleframe.app config; hosts rebind any key. Unconfigured,
 * the transport throws loud on first use (TStore posture), never
 * silent-drop.
 */
final class AppCommand extends Command
{
    private const ACTIONS = ['register', 'list', 'calls', 'run'];

    protected $signature = 'teleframe:app
        {action : register|list|calls|run}
        {--install : calls: probe-install the app-call schedules through the schedule seam}
        {--app= : run: the app name (registry name)}
        {--call= : run: the scheduled call name}
        {--raw : run: print the WHOLE call result (default prints its shape only — results carry account data)}';

    protected $description = 'Bot/user app framework: register (re-register defined apps onto the pipeline), list (registry rows), calls (installSpec preview / probe-install), run (one scheduled call now)';

    public function handle(): int
    {
        $action = (string) $this->argument('action');
        if (! in_array($action, self::ACTIONS, true)) {
            $this->error("unknown action \"{$action}\" — expected ".implode('|', self::ACTIONS).'.');

            return self::FAILURE;
        }

        try {
            return match ($action) {
                'register' => $this->register(),
                'list' => $this->listApps(),
                'calls' => $this->calls(),
                'run' => $this->runCallProbe(),
            };
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function register(): int
    {
        $runtime = $this->service();
        $names = [];
        foreach ($runtime->registry()->all() as $app) {
            // The apps are ALREADY defined in the registry (seeded at boot
            // / resolve time) — "re-register" installs their triggers onto
            // the shared pipeline only. AppRuntime::register() would
            // re-define and trip the registry's first-exact-match duplicate
            // guard, so the responder install is used directly.
            $runtime->responder()->install($app);
            $names[] = $app->name;
        }

        if ($names === []) {
            $this->info('No apps defined (teleframe.app.apps is empty) — nothing to register.');

            return self::SUCCESS;
        }

        $this->info('Registered '.count($names).' app(s): '.implode(', ', $names)
            .' — triggers live on the shared handler table under tag app:{name}.');

        return self::SUCCESS;
    }

    private function listApps(): int
    {
        $apps = $this->service()->registry()->all();
        if ($apps === []) {
            $this->info('No apps defined (teleframe.app.apps is empty).');

            return self::SUCCESS;
        }

        $this->table(['name', 'mode', 'triggers', 'calls'], array_map(
            static fn (AppDefinition $app): array => [
                $app->name,
                $app->mode->value,
                count($app->triggers),
                count($app->calls),
            ],
            $apps,
        ));

        return self::SUCCESS;
    }

    private function calls(): int
    {
        $apps = $this->service()->registry()->all();
        if ($apps === []) {
            $this->info('No apps defined (teleframe.app.apps is empty) — no installSpec to preview.');

            return self::SUCCESS;
        }

        if ((bool) $this->option('install')) {
            $status = $this->service()->installCalls();
            $this->table(['schedule', 'status'], array_map(
                static fn (string $id, string $state): array => [$id, $state],
                array_keys($status),
                array_values($status),
            ));
            $this->info('Probe-installed '.count($status).' app-call schedule(s) through the schedule seam — NullSchedulePublisher reports "skipped (null schedule driver)".');

            return self::SUCCESS;
        }

        $spec = new AppCallSpec;
        $rows = [];
        foreach ($apps as $app) {
            foreach ($spec->installSpec($app) as $id => $payload) {
                $rows[] = [
                    $id,
                    $payload['interval_seconds'],
                    $payload['activity'],
                    $payload['app'],
                    $payload['call'],
                ];
            }
        }

        if ($rows === []) {
            $this->info('No app-call schedules defined (no app carries calls).');

            return self::SUCCESS;
        }

        $this->table(['schedule', 'interval_seconds', 'activity', 'app', 'call'], $rows);
        $this->info('InstallSpec preview — pass --install to probe-install through the schedule seam.');

        return self::SUCCESS;
    }

    private function runCallProbe(): int
    {
        $app = (string) $this->option('app');
        $call = (string) $this->option('call');
        if ($app === '' || $call === '') {
            $this->error('run needs --app= and --call=. Example: php artisan teleframe:app run --app=echo-bot --call=status-ping');

            return self::FAILURE;
        }

        $result = $this->service()->runCall($app, $call);

        // A call result is whatever the app's method returns on the wire — a
        // chat history, an exportLoginToken, anything the app defines. The
        // default output is therefore the SHAPE, never the values; --raw is
        // the explicit, operator-typed opt-in for the whole payload (and even
        // then a result that is not JSON-encodable is reported as such, never
        // var_export()ed: that renders object internals, credentials included).
        if ((bool) $this->option('raw')) {
            $encoded = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
            $this->line($encoded === false
                ? 'result is not JSON-encodable ('.$this->describe($result).') — nothing printed.'
                : $encoded);
        } else {
            $this->line('result: '.$this->describe($result));
            $this->comment('values withheld — pass --raw to print the whole result.');
        }

        $this->info("Ran call {$call} on app {$app} (offline probe — no schedule host required).");

        return self::SUCCESS;
    }

    /**
     * A value-free description of a call result: the type, and for a
     * structure the field names with their types and sizes. Enough to prove
     * the call reached the wire and came back shaped, with nothing of the
     * payload on stdout.
     */
    private function describe(mixed $result): string
    {
        if (! is_array($result)) {
            // is_string/is_object rather than a blanket (string) cast: a
            // resource or a closure must not be rendered into a description.
            if (is_string($result)) {
                return 'string('.strlen($result).')';
            }
            if (is_object($result)) {
                return $result::class;
            }
            if (is_resource($result)) {
                return 'resource';
            }

            return get_debug_type($result);
        }

        $rows = [];
        foreach ($result as $key => $value) {
            $rows[] = $key.'='.$this->describeValue($value);
        }

        return 'array{'.implode(', ', $rows).'}';
    }

    /** The type and size of one field, never its content. */
    private function describeValue(mixed $value): string
    {
        if (is_array($value)) {
            return 'array('.count($value).')';
        }
        if (is_string($value)) {
            return 'string('.strlen($value).')';
        }
        if (is_object($value)) {
            return $value::class;
        }
        if (is_resource($value)) {
            return 'resource';
        }

        return get_debug_type($value);
    }

    private function service(): AppRuntime
    {
        $service = $this->getLaravel()->make(AppSupport::SERVICE_KEY);
        if (! $service instanceof AppRuntime) {
            throw new RuntimeException(AppSupport::SERVICE_KEY.' must bind a '.AppRuntime::class.'.');
        }

        return $service;
    }
}
