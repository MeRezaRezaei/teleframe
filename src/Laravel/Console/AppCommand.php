<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
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
            //
            // A refused install is REPORTED, not fatal (F16-5): one app's
            // unreachable trigger must not abort the whole run and hide the apps
            // that did install. handle() catches RuntimeException, which is NOT
            // InvalidArgumentException — without this guard the refusal left
            // artisan as a bare stack trace with no app names on it.
            try {
                $runtime->responder()->install($app);
                $names[] = $app->name;
            } catch (InvalidArgumentException) {
                // Recorded on the responder; reported below with every refusal.
            }
        }

        $refusals = $runtime->responder()->refusals();
        if ($refusals !== []) {
            $this->error(count($refusals).' app(s) refused — their triggers would never run:');
            foreach ($refusals as $refusal) {
                $this->line('  - '.$refusal->getMessage());
            }
        }

        if ($names === []) {
            if ($refusals === []) {
                $this->info('No apps defined (teleframe.app.apps is empty) — nothing to register.');
            }

            return $refusals === [] ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Registered '.count($names).' app(s): '.implode(', ', $names)
            .' — triggers live on the shared handler table under tag app:{name}.');

        return $refusals === [] ? self::SUCCESS : self::FAILURE;
    }

    private function listApps(): int
    {
        $runtime = $this->service();
        $apps = $runtime->registry()->all();
        if ($apps === []) {
            $this->info('No apps defined (teleframe.app.apps is empty).');

            return self::SUCCESS;
        }

        // The STATUS column is the half this command must not skip (F16-5): an
        // app whose trigger could never win the first-match table is refused at
        // install (AppResponder::refuseShadowed) and provider boot reports it
        // rather than dying. "installed" here means the app's triggers are on the
        // shared hot table; a refused app says so IN this table, so the operator
        // reads the reason without turning on a log.
        $this->table(['name', 'mode', 'triggers', 'calls', 'status'], array_map(
            static fn (AppDefinition $app): array => [
                $app->name,
                $app->mode->value,
                count($app->triggers),
                count($app->calls),
                self::status($runtime, $app),
            ],
            $apps,
        ));

        $refused = $runtime->responder()->refusals();
        if ($refused !== []) {
            $this->error(count($refused).' app(s) refused:');
            foreach ($refused as $refusal) {
                $this->line('  - '.$refusal->getMessage());
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * What the shared handler table actually holds for this app: the trigger
     * tag is the identity proof, and a refusal recorded at install is the
     * negative of it.
     */
    private static function status(AppRuntime $runtime, AppDefinition $app): string
    {
        if ($runtime->responder()->isInstalled($app->name)) {
            return 'installed';
        }

        return 'REFUSED — see below';
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

            // THE NO-WORK CONTRACT, enforced (the same rule as
            // `teleframe:tstore schedules --install`, which had the identical
            // hole). `--install` exists to arm these schedules; when the seam
            // skipped every one of them no job will fire, and the command
            // returned SUCCESS anyway — so a deploy step read "app-call
            // schedules armed" over a visible no-op.
            //
            // The table above still prints, per-schedule reason included: WHICH
            // schedules would have been armed is the actionable half of a
            // refusal.
            $armed = 0;
            foreach (array_values($status) as $state) {
                if (! self::isSkippedStatus((string) $state)) {
                    $armed++;
                }
            }

            if ($status !== [] && $armed === 0) {
                $this->error(
                    'NOTHING ARMED: the schedule seam skipped all '.count($status).' app-call schedule(s) — see the status column above for why. '
                    .'Nothing was installed, so this exits non-zero rather than reporting a successful probe-install.'
                );

                return self::FAILURE;
            }

            $this->info($armed.' of '.count($status).' app-call schedule(s) armed through the schedule seam'
                .' — NullSchedulePublisher reports "skipped (null schedule driver)".');

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

    /**
     * Whether a publisher status string reports that NOTHING was armed.
     *
     * Mirrors TstoreCommand::isSkippedStatus(), deliberately duplicated
     * rather than shared: the two commands are separate entry points with
     * separate seams, and a shared static would couple them for one
     * two-line prefix test. The rule is the same on both sides — the check
     * never assumes failure, it only refuses to assume success.
     */
    private static function isSkippedStatus(string $status): bool
    {
        return str_starts_with($status, 'skipped') || $status === 'not reported by the publisher';
    }
}
