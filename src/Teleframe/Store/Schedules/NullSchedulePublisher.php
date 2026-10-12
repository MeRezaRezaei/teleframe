<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules;

/**
 * NullSchedulePublisher (plan W8): the default seam — schedules off.
 * install() records nothing (returns per-schedule 'skipped' state) and
 * describe() explains how to switch on a real scheduler. The store is
 * fully functional without schedules: they are convergence conveniences
 * (registry sweep, cache prune, media index), not correctness gates.
 */
final class NullSchedulePublisher implements SchedulePublisher
{
    public function install(array $schedules): array
    {
        $status = [];
        foreach ($schedules as $id => $spec) {
            $status[$id] = 'skipped (null schedule driver)';
        }

        return $status;
    }

    /**
     * What the operator reads FIRST when a job is not firing. So it names keys
     * THAT EXIST AND ARE READ.
     *
     * It used to say "Set teleframe.tstore.schedules=temporal (Temporal at
     * teleframe.tstore.schedules_dsn…)". Both keys exist in config and NEITHER
     * is read by any code: the tstore seam is hardcoded to this very class
     * (TeleframeServiceProvider binds `teleclient.tstore.schedules` to
     * `new NullSchedulePublisher` in a closure that takes no config), so
     * TELEFRAME_TSTORE_SCHEDULES=temporal changed nothing at all. An
     * instruction an operator can follow to the letter and still get nothing is
     * the dead-config failure in its worst shape, and it was printed by the very
     * class that means "off".
     *
     * The keys listed here are the seams this package can actually describe,
     * all four of them spelled out in FULL, never abbreviated, because
     * SchedulePublisherDescriptionTest extracts every `teleframe.*` token out
     * of this text and proves a provider really reads it: a key that cannot
     * be checked is a key that will drift back into being a lie.
     *
     * `teleframe.tstore.schedules` and `teleframe.notify.schedules` were
     * deliberately NOT named while nothing called install() on those seams
     * (`teleframe:tstore schedules` and the notify command only printed
     * describe()), so a reader who set a driver there would still get no
     * job. THAT REASON IS NOW GONE, and this text follows the reason rather
     * than the history: both seams have an arming point — `teleframe:tstore
     * schedules --install` (StoreMaintenanceScheduleSpec) and
     * `teleframe:notify schedules --install` — so both keys belong here.
     * The machine check that matters is the one below: every key this text
     * DOES name must have a real reader AND a real arming path, which is
     * what stops it drifting back.
     *
     * 'temporal' needs the optional temporal/sdk (composer require
     * temporal/sdk); without it the providers REFUSE at resolve time by name
     * rather than degrading to this class.
     */
    public function describe(): string
    {
        return "Schedule driver: null. Nothing is scheduled — no job will fire.\n"
            ."To arm them, set one of the drivers that are actually read, then run that seam's --install:\n"
            .'  - teleframe.app.schedules=temporal, then teleframe:app calls --install '
            ."(teleframe.app.schedules_dsn, teleframe.app.schedules_task_queue)\n"
            .'  - teleframe.store.backup.schedule.publisher=temporal, then teleframe:tstore schedule '
            .'(teleframe.store.backup.schedule.dsn, teleframe.store.backup.schedule.task_queue)'."\n"
            .'  - teleframe.tstore.schedules=temporal, then teleframe:tstore schedules --install '
            ."(teleframe.tstore.schedules_dsn, teleframe.tstore.schedules_namespace)\n"
            .'  - teleframe.notify.schedules=temporal, then teleframe:notify schedules --install '
            .'(teleframe.notify.schedules_dsn)'."\n"
            ."Setting a driver alone arms NOTHING: install() is what creates or refreshes each schedule.\n"
            ."'temporal' needs the optional temporal/sdk (composer require temporal/sdk); "
            .'with it absent the provider refuses by name rather than staying off.';
    }
}
