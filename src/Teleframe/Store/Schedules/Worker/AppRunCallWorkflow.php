<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules\Worker;

use Temporal\Workflow;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

/**
 * AppRunCallWorkflow — the workflow behind the app-call family's workflow type
 * `telegram.app.run-call {app} {call}` (AppCallSpec::installSpec()).
 *
 * WHY IT CARRIES NO #[WorkflowMethod] NAME. The type embeds the app and call
 * names, so there is no single static name to declare on the class. Temporal
 * resolves a workflow by the NAME THE SERVER SENDS, and
 * `WorkflowPrototype::__construct(string $name, …)` takes it at construction —
 * so `TemporalWorker` registers THIS CLASS once per declared (app, call) pair
 * under that pair's exact type string. A static attribute would serve exactly
 * one app-call schedule no matter how many a host declares; the dynamic
 * registration serves all of them, and serves nothing else.
 *
 * `#[WorkflowInterface]` is still required on the class: WorkflowReader asserts
 * it, and the attribute is what makes `newInstanceWithoutConstructor()`
 * instantiation legal.
 *
 * The app and call names are taken from the PAYLOAD, not re-parsed out of the
 * type: the payload is keyed `app` / `call` (AppCallSpec emits both), so the
 * routing does not parse a string.
 *
 * Plain-PHP constructible, no container, zero regex.
 */
#[WorkflowInterface]
final class AppRunCallWorkflow
{
    /**
     * @param  array<string, mixed>  $payload  carries `app` and `call`
     * @return \Generator<mixed, mixed, mixed, mixed>
     */
    #[WorkflowMethod]
    public function run(array $payload): \Generator
    {
        $app = (string) ($payload['app'] ?? '');
        $call = (string) ($payload['call'] ?? '');

        $activity = Workflow::newActivityStub(
            RunAppCallActivity::class,
            RunAppCallActivity::options(),
        );

        return yield $activity->runCall($app, $call, $payload);
    }
}
