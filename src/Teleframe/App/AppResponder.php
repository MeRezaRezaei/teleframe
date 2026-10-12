<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\App;

use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;

/**
 * AppResponder (design §2/§4 / gap G3) — the app→pipeline registration
 * bridge: turns one AppDefinition's trigger list into HOT registrations on
 * the SHIPPED HandlerRegistry, each under the identity tag `app:{name}`.
 * This is the ONLY new step the respond path adds (design §4): dispatch
 * keeps running the shipped UpdateDispatcher byte-for-byte, so echo
 * elimination, replay dedup and DI invoke apply to app handlers
 * automatically. Registrations are hot — the shared table is read live at
 * dispatch, so an app installed at runtime is picked up by every
 * already-running consumer without restart.
 *
 * Registration only — NEVER a dispatch-path change. The shared hot table
 * is append-only by design (the shipped HandlerRegistry has no removal
 * API and is deliberately untouched: hard rule), so uninstall() works in
 * the design §11 registry-snapshot posture: it snapshots the live table
 * and diffs it by the app's tag, counting the registrations that carry
 * `app:{name}` — the identity hook §11 names as the contract. Rewriting
 * the shared table would be an engine change this module never makes.
 *
 * Plain-PHP constructible, zero container/facade calls: the handler table
 * defaults to HandlerRegistry::instance() — the one shared hot table per
 * process — while tests inject a plain table.
 */
final class AppResponder
{
    private readonly HandlerRegistry $handlerTable;

    public function __construct(
        ?HandlerRegistry $handlerTable = null,
    ) {
        $this->handlerTable = $handlerTable ?? HandlerRegistry::instance();
    }

    /**
     * Register every trigger of the app onto the shared handler table under
     * the identity tag `app:{name}` — one `on()` per trigger, using the
     * shipped first-match grammar and the shipped $tag pass-through
     * (HandlerRegistry::on()).
     *
     * IDEMPOTENT PER APP NAME (reachability fix). install() runs in EVERY
     * update-consuming process — the provider boots it, and
     * `teleframe:app register` runs it again in that same process. The
     * shared table is append-only, so a second install of the same app
     * would register every trigger twice and a first-match-wins table
     * would then run the same closure once per copy. A name is
     * first-exact-match in the registry (a definition can never change
     * under a name), so an already-installed app is a no-op: the tag is the
     * proof that this process already has it.
     *
     * @return int count of triggers installed (0 when already installed)
     */
    public function install(AppDefinition $app): int
    {
        if ($this->isInstalled($app->name)) {
            return 0;
        }

        $installed = 0;

        foreach ($app->triggers() as $trigger) {
            $this->handlerTable->on(
                $trigger->match,
                $trigger->handler,
                $trigger->priority,
                $trigger->onOwn,
                'app:'.$app->name,
            );

            $installed++;
        }

        return $installed;
    }

    /**
     * Uninstall one app's registrations from the shared hot table — the
     * design §11 registry-snapshot posture: snapshot the live table and
     * diff it by tag, counting the registrations whose tag is `app:{name}`.
     * That count IS what an uninstall removes from the app's footprint on
     * the pipeline; the shared table itself is never rewritten (append-only
     * by the registration-only rule — a table mutation would be a
     * dispatch-path engine change this module deliberately never makes).
     *
     * @return int count of `app:{name}`-tagged registrations in the live table
     */
    public function uninstall(string $name): int
    {
        return $this->taggedCount($name);
    }

    /**
     * Is this app's trigger set already live on the shared table in THIS
     * process? The `app:{name}` tag is the proof (identity hook §11).
     */
    public function isInstalled(string $name): bool
    {
        return $this->taggedCount($name) > 0;
    }

    /**
     * @return int registrations in the live table carrying `app:{name}`
     */
    private function taggedCount(string $name): int
    {
        $removed = 0;

        foreach ($this->handlerTable->all() as $handler) {
            if ($handler->tag === 'app:'.$name) {
                $removed++;
            }
        }

        return $removed;
    }
}
