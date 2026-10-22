<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\App;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Core\Schema\MethodRegistry;
use MeRezaRezaei\Teleframe\Handler\HandlerMatcher;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\Middleware\EchoEliminator;
use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Throwable;

/**
 * AppRuntime (design §2/§9) — THE orchestrator of the app framework: the
 * program surface a developer composes to register declarative apps
 * (registry + pipeline registration through AppResponder), install their
 * scheduled calls onto the EXISTING SchedulePublisher seam, and run one
 * scheduled call through the per-mode transport at call time. Every seam
 * is constructor-injected and plain-PHP constructible — no container, no
 * facade calls inside (standalone-smoke-green, compose-not-own).
 *
 * Composed chain (design §1/§5):
 *   register()  → AppRegistry::define + AppResponder::install
 *                 (SHIPPED HandlerRegistry, tag 'app:{name}', untouched pipeline)
 *   installCalls() → AppCallSpec::installSpec per registered app
 *                    → SchedulePublisher::install()  (null default → 'skipped')
 *   runCall()   → registry resolve → mode-check (§5/§6 Q4: a call whose
 *                 catalog API mismatches the app's own mode refuses LOUD)
 *                 → AppTransportResolver (mode → scope at call time)
 *                 → transport->call(method, params)  [the catalog surface]
 *                 → PSR-3 error log + ALWAYS rethrow (schedule host owns retry)
 *
 * Failure posture (spec §7): every runCall failure is logged through the
 * PSR-3 logger at `error` and rethrown unchanged — logging never
 * swallows; refuse-loud for unknown apps/calls, mode mismatches and
 * unavailable transports (AppException), never silent-drop.
 *
 * Zero regex.
 */
final class AppRuntime
{
    private readonly AppTransportResolver $transports;

    private readonly AppResponder $responder;

    public function __construct(
        private readonly AppRegistry $registry,
        private readonly SchedulePublisher $schedules,
        ?callable $transport = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?\Closure $now = null,
        private readonly ?CacheInterface $sends = null,
    ) {
        $this->transports = new AppTransportResolver($transport);

        $this->responder = new AppResponder;
    }

    /**
     * The one-call sugar the facade's own examples use (2026-09-26 reachability
     * fix, BREAK 3): declare a declarative app in the shipped grammar AND
     * install its triggers in the same breath.
     *
     * This is what the facade docblock USED to advertise as `define()` and
     * did not have — `TelegramApp::define(...)` raised
     * "Call to undefined method AppRuntime::define()", so the documented
     * first step of every example was fatal. The docblock also told readers
     * to pipe `TelegramApp::list()`'s array into register(), whose parameter
     * is an AppDefinition.
     *
     * @param  list<AppTrigger>  $triggers
     * @param  list<AppCall>  $calls
     * @return string the app name (for chaining / listing)
     */
    public function define(string $name, AppMode $mode, array $triggers = [], array $calls = []): string
    {
        return $this->register(AppDefinition::define($name, $mode, $triggers, $calls));
    }

    /**
     * The registry rows for listing: name ⇒ {name, mode, triggers, calls,
     * callable}. NEVER a bare definition row the caller is meant to feed
     * somewhere — `list()` is a VIEW, `resolve()` is the lookup.
     *
     * @return array<string, array{name: string, mode: string, triggers: int, calls: int}>
     */
    public function list(): array
    {
        return $this->apps();
    }

    /**
     * The installSpec preview for every registered app — what
     * SchedulePublisher::install() WOULD be handed, per call, WITHOUT
     * installing (the read side of `calls`; `installCalls()` is the write).
     *
     * THE MERGE, AND WHY IT IS array_replace() AND NOT `+=` (F16-6).
     *
     * `+=` on a name⇒payload map is FIRST-wins: on a colliding key it keeps
     * the first payload and silently DROPS the second. That is the exact
     * shape this package refuses everywhere else — AppRegistry::define()
     * refuses a duplicate app name loudly and AppDefinition::define() refuses
     * a within-app duplicate call name loudly — so a silent fold here is the
     * one place the posture quietly stops holding. array_replace() is
     * LAST-wins: still one row per key, but the semantics are named in the
     * code instead of being a side effect of the operator.
     *
     * The collision is LATENT, not live, and that is measured rather than
     * assumed. AppCallSpec::callId() is a function of the (app, call) PAIR
     * plus a 12-hex sha256 digest, the registry refuses a duplicate app name,
     * and a within-app duplicate call name is refused at declare time, so no
     * two reachable payloads carry the same key today. The two operators are
     * therefore IDENTICAL on every reachable input — which is precisely why
     * this line needs to say so on the page, and why no behavioural test can
     * witness the difference. tests/App/AppRuntimeCallMergeTest.php is the
     * gate that does: it fails the moment `+=` comes back to either merge
     * site, and it fails again if this comment stops naming the collision.
     *
     * @return array<string, array{interval_seconds: int, activity: string, app: string, call: string}>
     */
    public function calls(): array
    {
        $spec = [];

        foreach ($this->registeredApps() as $app) {
            $spec = array_replace($spec, (new AppCallSpec)->installSpec($app));
        }

        return $spec;
    }

    /**
     * Resolve one app's transport scope at call time — the read/send seam
     * under read()/answer() and the escape hatch for a host that wants the
     * raw scope (getDialogs, getHistory, sendMedia, …) without the catalog
     * round trip. Credentials are resolved HERE, never at declare time, and
     * an unconfigured host throws loud through the resolver.
     */
    public function transport(?AppMode $mode = null): mixed
    {
        $resolved = $mode ?? AppMode::User;

        return $this->transports->resolve($resolved);
    }

    /**
     * READ the conversation — the owner's "connect my ai to read my telegram
     * messages". Goes through the app's own mode transport: the user mode's
     * UserAccountScope::getHistory(), which normalizes the peer for MTProto.
     *
     * Bot mode refuses LOUD: the Bot API has no history-read method, so
     * there is nothing honest to call. A host that wants bot reads uses
     * transport()->call('messages.getHistory', …) against a user scope.
     *
     * @param  int|string|array  $peer  chat id, username or a normalized peer array
     * @return list<array<string, mixed>> the `messages` rows, newest last
     *
     * @throws AppException when the resolved transport cannot read history
     */
    public function read(int|string|array $peer, int $limit = 50, ?AppMode $mode = null, int $offsetId = 0): array
    {
        $scope = $this->transport($mode);

        if (! is_object($scope) || ! method_exists($scope, 'getHistory')) {
            throw new AppException(
                'app read: the resolved '.($mode?->transportName() ?? 'user')
                .' transport has no getHistory() surface — the Bot API cannot read message history;'
                .' use mode user, or transport()->call(\'messages.getHistory\', …).',
            );
        }

        $result = $scope->getHistory($peer, $limit, $offsetId);
        $messages = is_array($result) ? ($result['messages'] ?? []) : [];

        return is_array($messages) ? array_values($messages) : [];
    }

    /**
     * ANSWER the conversation — the owner's "… and answer them".
     *
     * Sends through the same transport seam as read(), and records the
     * result in the Q2d send-time elimination registry so the reply's own
     * echo does not re-enter the pipeline and start a loop (the EchoEliminator
     * is on by default; an app opts out per trigger with onOwn: true).
     *
     * The registry write is BEST EFFORT and never fails the send: the reply
     * reached Telegram, and losing the echo bookkeeping degrades one
     * middleware, not the answer. Nothing about a message's CONTENT is ever
     * logged — only the peer reference and the returned ids.
     *
     * @return array<string, mixed> the transport's send result
     */
    public function answer(
        int $accountId,
        int|string|array $peer,
        string $text,
        ?AppMode $mode = null,
        array $options = [],
    ): array {
        $scope = $this->transport($mode);

        if (! is_object($scope) || ! method_exists($scope, 'sendMessage')) {
            throw new AppException(
                'app answer: the resolved '.($mode?->transportName() ?? 'user')
                .' transport has no sendMessage() surface — a send path is required to answer.',
            );
        }

        $result = $scope->sendMessage($peer, $text, $options);
        $result = is_array($result) ? $result : [];

        $this->rememberSend($accountId, $result);

        return $result;
    }

    /**
     * Register one declarative app: define it in the registry AND install
     * its triggers onto the shared handler pipeline (AppResponder, tag
     * 'app:{name}'). Returns the app name for chaining/listing.
     */
    public function register(AppDefinition $app): string
    {
        $this->registry->define($app);

        $this->responder->install($app);

        return $app->name;
    }

    /**
     * Install every registered app's scheduled calls onto the scheduled
     * seam: AppCallSpec::installSpec per app → SchedulePublisher::install()
     * → per-schedule status (the NullSchedulePublisher default answers
     * 'skipped (null schedule driver)' — mirrors how BackupScheduleSpec/
     * NotifyCommand install).
     *
     * THE MERGE IS THE SAME ONE calls() MAKES (F16-6), and carries the same
     * comment because this is the WRITE side: it is the payload that reaches
     * the schedule host, so a colliding key folded away here would arm one
     * app's cadence and silently drop the other app's call. array_replace()
     * (last-wins) rather than `+=` (first-wins, which drops the second
     * payload in silence); the collision is latent today because
     * AppCallSpec::callId() keys on the (app, call) pair and both duplicate
     * routes refuse loud. Same gate, same reasoning:
     * tests/App/AppRuntimeCallMergeTest.php.
     *
     * @return array<string, string> schedule id ⇒ install status
     */
    public function installCalls(): array
    {
        $payload = [];

        foreach ($this->registeredApps() as $app) {
            $payload = array_replace($payload, (new AppCallSpec)->installSpec($app));
        }

        return $this->schedules->install($payload);
    }

    /**
     * Run ONE scheduled call now (the offline probe / schedule-host
     * fire path): resolve the app, refuse calls whose catalog method
     * mismatches the app's own mode (design §5/§6 Q4), resolve the
     * per-mode transport and invoke the catalog method through the
     * transport's call(string, array) surface. Failures log at `error`
     * and ALWAYS rethrow — the schedule host owns retry.
     *
     * @return mixed the catalog method's result (array for the shipped scopes)
     *
     * @throws AppException unknown app/call, unconfigured or failing
     *                      transport, or a mode-mismatched call
     * @throws Throwable the underlying catalog failure, rethrown unchanged
     */
    public function runCall(string $app, string $call): mixed
    {
        $definition = $this->registry->resolve($app);
        $appCall = $this->callOf($definition, $call);

        $this->assertCatalogModeMatches($definition, $appCall);

        $transport = $this->transports->resolveFor($definition);
        if (! is_object($transport) || ! method_exists($transport, 'call')) {
            throw new AppException(
                "app '{$app}' call '{$call}': resolved transport has no call(string, array) catalog surface"
                .' — expected a UserAccountScope/BotClient-shaped scope.',
            );
        }

        /** @var callable(string, array<string, mixed>): mixed $invoke */
        $invoke = [$transport, 'call'];

        try {
            return $invoke($appCall->method, $appCall->params);
        } catch (Throwable $e) {
            $this->logger?->error('telegram.app.run-call.failed', [
                'app' => $app,
                'call' => $call,
                'method' => $appCall->method,
                'mode' => $definition->mode->value,
                'exception' => $e,
                'at' => $this->now(),
            ]);

            throw $e;
        }
    }

    /** The registry seam (shared with the provider/facade). */
    public function registry(): AppRegistry
    {
        return $this->registry;
    }

    /**
     * One app by name (BotMap::for() posture): unknown → loud AppException.
     */
    public function resolve(string $name): AppDefinition
    {
        return $this->registry->resolve($name);
    }

    /** The app→pipeline registration bridge (SHIPPED HandlerRegistry). */
    public function responder(): AppResponder
    {
        return $this->responder;
    }

    /** The SchedulePublisher seam the app-call schedules land on. */
    public function schedules(): SchedulePublisher
    {
        return $this->schedules;
    }

    /**
     * Registered rows for listing (Facade/AppCommand list): app name ⇒
     * {name, mode, trigger count, call count}.
     *
     * @return array<string, array{name: string, mode: string, triggers: int, calls: int}>
     */
    public function apps(): array
    {
        $rows = [];

        foreach ($this->registeredApps() as $app) {
            $rows[$app->name] = [
                'name' => $app->name,
                'mode' => $app->mode->value,
                'triggers' => count($app->triggers()),
                'calls' => count($app->calls()),
            ];
        }

        return $rows;
    }

    /**
     * @return list<AppDefinition> the registry rows, definition-typed
     *                             (robust against list or name-keyed shapes)
     */
    private function registeredApps(): array
    {
        $apps = [];

        foreach ($this->registry->all() as $app) {
            $apps[] = $app;
        }

        return $apps;
    }

    /** One call by id within an app; unknown → loud AppException. */
    private function callOf(AppDefinition $app, string $call): AppCall
    {
        foreach ($app->calls() as $appCall) {
            if ($appCall->name === $call) {
                return $appCall;
            }
        }

        throw new AppException("app '{$app->name}' has no scheduled call '{$call}'.");
    }

    /**
     * The §5/§6 Q4 gate: a call's catalog method must belong to the API
     * the app's own mode transports (bot ↔ bot-http BotClient, user ↔
     * mtproto UserAccountScope — the design §6 slice matrix). Unknown
     * methods and mode-mismatched calls refuse LOUD, never silent-drop;
     * per-call transport override is wave 3.
     */
    private function assertCatalogModeMatches(AppDefinition $app, AppCall $call): void
    {
        try {
            $api = MethodRegistry::apiOf($call->method);
        } catch (InvalidArgumentException $e) {
            throw new AppException(
                "app '{$app->name}' call '{$call->name}': unknown catalog method [{$call->method}].",
                $e,
            );
        }

        $expected = $app->mode === AppMode::Bot ? 'bot-http' : 'mtproto';
        if ($api !== $expected) {
            throw new AppException(
                "app '{$app->name}' call '{$call->name}' method [{$call->method}] is a {$api} catalog method"
                .", not usable by a {$app->mode->value}-mode app (design §5/§6 Q4: mode-mismatched calls refuse loud).",
            );
        }
    }

    /** The injected clock, default time() — the §9 test seam. */
    private function now(): int
    {
        return ($this->now ?? static fn (): int => time())();
    }

    /**
     * Record a framework send in the Q2d elimination registry (best effort —
     * the same PSR-16 seam UpdateDispatcher holds). A host with no cache
     * binding, or a send whose result carries no id, simply has nothing to
     * record; the reply itself already went out.
     *
     * @param  array<string, mixed>  $result
     */
    private function rememberSend(int $accountId, array $result): void
    {
        if ($this->sends === null) {
            return;
        }

        $row = ['sent_at' => $this->now()];
        foreach (['random_id', 'msg_id', 'id'] as $field) {
            $value = (string) ($result[$field] ?? '');
            if ($value !== '') {
                $row[$field === 'id' ? 'msg_id' : $field] = $value;
            }
        }

        if (! isset($row['msg_id']) && ! isset($row['random_id'])) {
            return;
        }

        try {
            (new EchoEliminator($this->sends, new HandlerMatcher(HandlerRegistry::instance())))
                ->remember($accountId, $row);
        } catch (Throwable $e) {
            // Echo bookkeeping is never worth losing the answer over; it is
            // still never silent.
            $this->logger?->warning('telegram.app.answer.echo-registry-failed', [
                'account_id' => $accountId,
                'exception' => $e,
            ]);
        }
    }
}
