<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Ingest\UpdateRouter;
use MeRezaRezaei\Teleframe\Ingest\UpdateRoutingCache;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;

/**
 * artisan teleframe:routing — the write path that turns routing ON.
 *
 * The engine's dispatch default is store_only (loop prevention: a notify/log
 * channel must never become an app input), which is safe but leaves a
 * first-release host with NO way to reach act_on: the UpdateRouter answers
 * store_only for every peer, so RoutingEventGateway::emit() never dispatches
 * UpdateStored, so handlers, the Centrifugo feed and TStore are all dead on
 * the normal path. Nothing in the package could ever write the opt-in row.
 *
 * This command is that missing opt-in: it upserts ONE tg_update_routing row
 * through the UpdateRoutingRule model, so the already-wired
 * RoutingSettingsObserver hot-reloads the row into the Redis routing table
 * and publishes on the reload channel — no daemon restart. The account is a
 * REQUIRED argument and the account-wide catch-all peer (0,0) additionally
 * requires --account-wide, so no run can silently flip a whole tenant.
 *
 * Write through the Eloquent model, never the query builder: the observer
 * that refreshes the hot cache hangs off the model lifecycle.
 */
final class RoutingModeCommand extends Command
{
    protected $signature = 'teleframe:routing
        {account : Telegram account id (REQUIRED scope — never a whole tenant by accident)}
        {--mode=act_on : act_on (store AND dispatch app events) | store_only (store only, no events)}
        {--peer= : Peer type — 1=user 2=chat 3=channel. Omit for the account catch-all (0,0)}
        {--peer-id= : Peer id. Required with --peer}
        {--account-wide : Confirm the account-wide catch-all peer (0,0), which flips every unmarked peer}
        {--priority=0 : Rule priority; higher wins when several rules mark one peer}
        {--dry-run : Print the resulting mode and write nothing}';

    protected $description = 'Set an account/peer update-routing mode (act_on turns dispatch on) and hot-reload it into Redis';

    public function handle(): int
    {
        $accountId = (int) $this->argument('account');
        if ($accountId <= 0) {
            $this->error('account must be a positive integer — routing is per account, not global.');

            return self::FAILURE;
        }

        $mode = (string) $this->option('mode');
        if (! in_array($mode, [UpdateRoutingRule::MODE_ACT_ON, UpdateRoutingRule::MODE_STORE_ONLY], true)) {
            $this->error("unknown --mode \"{$mode}\" — expected act_on or store_only.");

            return self::FAILURE;
        }

        $scope = $this->resolveScope();
        if ($scope === null) {
            return self::FAILURE;
        }

        [$peerType, $peerId] = $scope;

        $this->table(
            ['field', 'value'],
            [
                ['account', $accountId],
                ['peer_type', $peerType],
                ['peer_id', $peerId],
                ['scope', $peerType === UpdateRoutingRule::DEFAULT_PEER_TYPE
                    ? 'account catch-all (every unmarked peer)'
                    : 'single peer'],
                ['mode', $mode],
                ['priority', (int) $this->option('priority')],
            ],
        );

        if ($this->option('dry-run')) {
            $this->line('dry run — nothing written.');

            return self::SUCCESS;
        }

        try {
            UpdateRoutingRule::query()->updateOrCreate(
                [
                    'account_id' => $accountId,
                    'peer_type' => $peerType,
                    'peer_id' => $peerId,
                ],
                [
                    'mode' => $mode,
                    'priority' => (int) $this->option('priority'),
                ],
            );
        } catch (\Throwable $e) {
            // The observer refreshes Redis inside the model lifecycle, so a
            // broken hot cache surfaces here. Refuse loudly: a row committed
            // with a stale hot key still routes wrong on the hot path.
            $this->error("routing write failed: {$e->getMessage()}");

            if ($this->rowMode($accountId, $peerType, $peerId) === $mode) {
                $this->error(
                    "DB row IS set to {$mode}, but the hot reload did not complete — "
                    .'the Redis routing table may be stale. Check the bus redis before trusting the hot path.'
                );
            }

            return self::FAILURE;
        }

        $resolved = (new UpdateRouter(DB::connection()))->mode($accountId, $peerType, $peerId);

        $this->table(
            ['field', 'value'],
            [
                ['written', $mode],
                ['rule row', "tg_update_routing account={$accountId} peer=({$peerType},{$peerId})"],
                ['router resolves (DB source of truth)', $resolved],
                ['hot routing key', UpdateRoutingCache::KEY.UpdateRoutingCache::FIELD_SEPARATOR.$accountId],
                ['reload channel', StreamSchema::RELOAD_CHANNEL],
                ['act_on means', 'updates from this scope are stored AND dispatched as UpdateStored (handlers, feed, tstore)'],
                ['store_only means', 'updates are stored, never dispatched (notify/log channels — no event loop)'],
            ],
        );

        if ($resolved !== $mode) {
            $this->warn("router resolves \"{$resolved}\", not \"{$mode}\" — a higher-priority rule for this peer still wins.");

            return self::FAILURE;
        }

        $this->info("routing mode for account {$accountId} peer ({$peerType},{$peerId}) is now {$resolved}.");

        return self::SUCCESS;
    }

    /**
     * Resolve the (peer_type, peer_id) scope the operator named, refusing
     * loudly on an incomplete or accidentally tenant-wide invocation.
     *
     * @return array{0: int, 1: int}|null null after printing the refusal
     */
    private function resolveScope(): ?array
    {
        $peer = $this->option('peer');
        $peerId = $this->option('peer-id');

        if ($peer === null && $peerId === null) {
            if (! $this->option('account-wide')) {
                $this->error(
                    'no peer given: that is the account-wide catch-all (0,0), which flips every '
                    .'unmarked peer for this account. Pass --peer/--peer-id for one peer, or '
                    .'re-run with --account-wide to confirm the whole account.'
                );

                return null;
            }

            return [UpdateRoutingRule::DEFAULT_PEER_TYPE, UpdateRoutingRule::DEFAULT_PEER_ID];
        }
        if ($peer === null || $peerId === null) {
            $missing = $peer === null ? '--peer' : '--peer-id';
            $this->error("--peer and --peer-id must be given together; {$missing} is missing.");

            return null;
        }

        if ($this->option('account-wide')) {
            $this->warn('--account-wide is meaningless with an explicit --peer/--peer-id; writing the single peer only.');
        }

        $peerType = (int) $peer;
        $peerIdInt = (int) $peerId;

        if ($peerType < 1 || $peerType > 3) {
            $this->error("--peer must be 1 (user), 2 (chat) or 3 (channel) — got \"{$peer}\".");

            return null;
        }

        if ($peerIdInt <= 0) {
            $this->error("--peer-id must be a positive Telegram id — got \"{$peerId}\".");

            return null;
        }

        return [$peerType, $peerIdInt];
    }

    private function rowMode(int $accountId, int $peerType, int $peerId): ?string
    {
        try {
            $row = DB::table('tg_update_routing')
                ->where('account_id', $accountId)
                ->where('peer_type', $peerType)
                ->where('peer_id', $peerId)
                ->orderByDesc('priority')
                ->first();
        } catch (\Exception) {
            return null;
        }

        return $row === null ? null : (string) $row->mode;
    }
}
