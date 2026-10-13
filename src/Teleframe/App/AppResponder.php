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
     * REFUSES AN UNREACHABLE TRIGGER (cross-app shadowing). HandlerMatcher is
     * first-match-wins over the table sorted by priority desc, stable. So when
     * two apps declare the SAME match at the SAME priority, the FIRST one
     * registered runs and the second is dead — with no error, no warning, and
     * both tags present in the table, so `teleframe:app` lists both apps as
     * installed. Nothing reported it; the proven case was app-a/app-b both
     * `on('updateNewMessage')` at default priority 0, matcher resolving
     * `app:app-a` and app-b's closure never being invoked.
     *
     * This install() used to do nothing about it. A silently dead handler is the
     * exact failure class this repo keeps paying to remove, so it is a HARD
     * refusal here rather than a warning nobody reads: the app's own triggers
     * are validated BEFORE anything is registered, so a refusal leaves the table
     * exactly as it was (no half-installed app).
     *
     * @return int count of triggers installed (0 when already installed)
     *
     * @throws \InvalidArgumentException when another app already holds an equal
     *                                   match at an equal priority
     */
    public function install(AppDefinition $app): int
    {
        if ($this->isInstalled($app->name)) {
            return 0;
        }

        $this->refuseShadowed($app);

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
     * Refuse before registering: for every trigger this app declares, is there
     * already a registration with the SAME match and the SAME priority that
     * belongs to somebody else?
     *
     * Only exact match + exact priority. A different match is not a conflict
     * (the two patterns are independent rows in the table), and a deliberately
     * different priority is the documented way to disambiguate — which of the two
     * wins is then the host's stated intent, not an accident.
     *
     * @throws \InvalidArgumentException
     */
    private function refuseShadowed(AppDefinition $app): void
    {
        $tag = 'app:'.$app->name;

        foreach ($app->triggers() as $trigger) {
            foreach ($this->handlerTable->all() as $registered) {
                if ($registered->match !== $trigger->match) {
                    continue;
                }

                if ($registered->priority !== $trigger->priority) {
                    continue;
                }

                // A same-app row is this app's own earlier trigger; the
                // within-app duplicate is AppDefinition's call-name rule's job
                // for calls, and for triggers the table's own first-match posture
                // is the host's stated intent.
                if ($registered->tag === $tag) {
                    continue;
                }

                throw new \InvalidArgumentException(sprintf(
                    'app [%s] trigger [%s] at priority %d is unreachable: [%s] already registered the same '
                    .'match at the same priority, and handler matching is first-match-wins, so this app\'s '
                    .'handler would never run. Give one of them a different priority, or a different match.',
                    $app->name,
                    $trigger->match,
                    $trigger->priority,
                    $this->describeOwner($registered->tag),
                ));
            }
        }
    }

    /** A readable owner name for the refusal: the tag, or the callable's shape. */
    private function describeOwner(?string $tag): string
    {
        if ($tag === null || $tag === '') {
            return 'an untagged handler';
        }

        return 'the tag ['.$tag.']';
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
