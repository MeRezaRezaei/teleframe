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

    /**
     * Every refusal install() has raised in THIS process, oldest first.
     *
     * install() keeps its loud contract (it throws), but a caller that must not
     * die on a bad declaration — provider boot() installs every configured
     * app, so one shadowed row would otherwise take down every process, artisan
     * included — needs the refusal as a VALUE to report and move on. The
     * exception is the module's own AppException naming the app and the
     * trigger, so `teleframe:app list` can show the same sentence the boot
     * reported. The app name is kept as a FIELD, not parsed back out of the
     * message, so a lookup cannot match a name that is a prefix of another.
     *
     * @var list<array{app: string, trigger: string, priority: int, exception: AppException}>
     */
    private array $refusals = [];

    public function __construct(
        ?HandlerRegistry $handlerTable = null,
    ) {
        $this->handlerTable = $handlerTable ?? HandlerRegistry::instance();
    }

    /**
     * The install() refusals recorded in this process, oldest first.
     *
     * @return list<AppException> empty when every app installed clean
     */
    public function refusals(): array
    {
        return array_map(static fn (array $refusal): AppException => $refusal['exception'], $this->refusals);
    }

    /**
     * The refusal recorded for one app name, or null when that app installed
     * clean (or was never installed in this process).
     */
    public function refusalFor(string $name): ?AppException
    {
        foreach ($this->refusals as $refusal) {
            if ($refusal['app'] === $name) {
                return $refusal['exception'];
            }
        }

        return null;
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
     * That guard compared match STRINGS and so missed the wider grammar: a
     * host's `onMessage()` catch-all `'*'`, a `'prefix*'`, or any higher-
     * priority superset row installed an app's trigger green and dead (F16-1).
     * refuseShadowed() now decides containment over the whole shipped grammar;
     * see its docblock for the rule and for what stays deliberately allowed.
     *
     * This install() used to do nothing about any of it. A silently dead handler
     * is the exact failure class this repo keeps paying to remove, so it is a
     * HARD refusal here rather than a warning nobody reads: the app's own
     * triggers are validated BEFORE anything is registered, so a refusal leaves
     * the table exactly as it was (no half-installed app). The refusal is ALSO
     * recorded on this responder (refusals()) before it propagates, so a caller
     * that must not die on it — provider boot installs every configured app —
     * can report it and continue.
     *
     * @return int count of triggers installed (0 when already installed)
     *
     * @throws \InvalidArgumentException when another owner's preceding row
     *                                   matches every update this app's
     *                                   trigger would match
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
     * Refuse before registering: would this app's trigger EVER run?
     *
     * THE RULE (F16-1). HandlerMatcher is first-match-wins over
     * HandlerRegistry::all() — priority DESC, stable tie-break by
     * registration order. A trigger appended to that table therefore sits
     * after every row already in it, and precedes it only when its priority is
     * strictly higher. So a trigger is DEAD exactly when some other owner's
     * row precedes it AND that row matches every update this trigger would
     * have matched. That is the whole class, and it is decidable with the
     * shipped grammar using string functions alone (covers() below) — no
     * regex, no matcher replay.
     *
     * WHAT USED TO BE MISSED. The shipped guard compared the match STRINGS, so
     * only two apps declaring the IDENTICAL pattern collided. The common cases
     * slipped through installed-green-and-never-run:
     *   - a host's `onMessage()` catch-all `'*'` at priority 0 — what
     *     HandlerRegistry::onMessage() registers — installed FIRST, which is the
     *     ordinary order when a host subscribes and then declares an app;
     *   - a `'prefix*'` row ahead of the exact match it contains;
     *   - a higher-priority `'*'`/`'prefix*'` row from any owner.
     * In all three, install() returned the trigger count, the `app:{name}` tag
     * sat on the table, isInstalled() was true, `teleframe:app list` showed the
     * app installed — and the handler never ran. Silent wrong, order-dependent.
     *
     * WHAT IS STILL ALLOWED, deliberately:
     *  - a different priority — priority IS the documented disambiguation lever
     *    and "the host has stated which one wins" is not an accident (pinned by
     *    a_different_priority_is_allowed_and_the_higher_one_wins);
     *  - disjoint patterns — two independent rows;
     *  - a pattern the other row does not cover, in either direction;
     *  - WITHIN-app overlap (the same app declaring `'*'` and an exact match):
     *    one app's two triggers are one program, and the table's own first-match
     *    posture over them is that app author's stated intent. Refusing it
     *    would break a legal shipping declaration to police a self-shadow. The
     *    consequence is stated here so it is not a hidden hole: a within-app
     *    narrower trigger behind the app's own catch-all stays dead, and the
     *    host who wants both must give them different priorities.
     *
     * @throws \InvalidArgumentException when this app's trigger could never win
     */
    private function refuseShadowed(AppDefinition $app): void
    {
        $tag = 'app:'.$app->name;

        foreach ($app->triggers() as $trigger) {
            foreach ($this->handlerTable->all() as $registered) {
                // A lower-priority existing row sorts AFTER the trigger about
                // to be appended, so the new trigger wins and the host's stated
                // priority stands. Not a refusal.
                if ($registered->priority < $trigger->priority) {
                    continue;
                }

                // A same-app row is this app's own earlier trigger — the
                // within-app exemption documented above.
                if ($registered->tag === $tag) {
                    continue;
                }

                if (! self::covers($registered->match, $trigger->match)) {
                    continue;
                }

                $refusal = new \InvalidArgumentException(sprintf(
                    'app [%s] trigger [%s] at priority %d is unreachable: %s already registered the match [%s] '
                    .'at priority %d, which matches every update this trigger would match, and handler matching is '
                    .'first-match-wins, so this app\'s handler would never run. Give one of them a different '
                    .'priority, or a different match.',
                    $app->name,
                    $trigger->match,
                    $trigger->priority,
                    $this->describeOwner($registered->tag),
                    $registered->match,
                    $registered->priority,
                ));

                // Recorded BEFORE it propagates: the throw is the loud contract
                // callers already depend on, and the record is what lets a
                // caller that must not die (provider boot) report it. Idempotent
                // per (app, trigger): provider boot and `teleframe:app register`
                // both install in the same process, and the same refusal must
                // not be counted twice.
                foreach ($this->refusals as $recorded) {
                    if ($recorded['app'] === $app->name
                        && $recorded['trigger'] === $trigger->match
                        && $recorded['priority'] === $trigger->priority) {
                        throw $refusal;
                    }
                }

                $this->refusals[] = [
                    'app' => $app->name,
                    'trigger' => $trigger->match,
                    'priority' => $trigger->priority,
                    'exception' => new AppException(
                        'app ['.$app->name.'] not installed: '.$refusal->getMessage(),
                        $refusal,
                    ),
                ];

                throw $refusal;
            }
        }
    }

    /**
     * Does $outer match EVERY update $inner matches — i.e. if $outer precedes
     * $inner in the table, does it swallow all of $inner's updates?
     *
     * The shipped grammar is decidable with string functions alone, so this is
     * a comparison of the three regex-free forms, never a matcher replay and
     * never a regex:
     *   '*'        covers everything
     *   'Prefix*'  covers every constructor starting with Prefix
     *   'Foo'      covers exactly 'Foo' — and nothing else, never 'FooBar'
     *
     * A pattern carrying a `%s` token is sscanf-shaped, so the only
     * containment this can decide for it is equality (handled first, and the
     * case the shipped guard already covered). It is never guessed: an
     * undecidable shape is reported as "does not cover", which leaves the
     * trigger installed exactly as it behaves today.
     */
    private static function covers(string $outer, string $inner): bool
    {
        if ($outer === $inner) {
            return true;
        }

        if (str_contains($outer, '%s') || str_contains($inner, '%s')) {
            return false;
        }

        if ($outer === '*') {
            return true;
        }

        // An exact outer covers only itself, which the equality above already
        // answered: 'Foo' does not cover 'FooBar'.
        if (! str_ends_with($outer, '*')) {
            return false;
        }

        $outerPrefix = substr($outer, 0, -1);

        // An exact inner under a prefix outer: 'updateNewMessage*' covers 'updateNewMessage'.
        if (! str_ends_with($inner, '*')) {
            return str_starts_with($inner, $outerPrefix);
        }

        // Two prefix forms: the shorter/equal prefix swallows the longer one
        // ('update*' covers 'updateNewMessage*', never the reverse).
        return str_starts_with(substr($inner, 0, -1), $outerPrefix);
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
