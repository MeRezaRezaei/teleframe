<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Backfill\BackfillWorker;
use MeRezaRezaei\Teleframe\Backfill\CursorStoreContract;
use MeRezaRezaei\Teleframe\Backfill\DialogDiscovery;
use MeRezaRezaei\Teleframe\Backfill\FetchQueue;
use MeRezaRezaei\Teleframe\Backfill\MemoryCursorStore;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\FloodWaitException;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Core\Types\InputUser;
use MeRezaRezaei\Teleframe\Laravel\MirrorIngesterFactory;
use MeRezaRezaei\Teleframe\Laravel\Models\TfIngestClue;
use RuntimeException;
use Throwable;

/**
 * artisan teleframe:backfill (plan Phase 3, Task 4): quota-sliced
 * history backfill for one account (--account) over a FetchQueue seeded
 * either from --peer (repeatable) or, with --full, from EVERY dialog the
 * account can see (DialogDiscovery) — each walked down to message id 1.
 *
 * Full history needs the dialogs path, not getDifference: Telegram bounds
 * the update window, so an account's whole history can only be recovered
 * by enumerating dialogs (messages.getDialogs) and crawling each peer
 * (messages.getHistory to id 1). --state prints the account's
 * updates.getState first.
 *
 * Each fetched page is stored through the P2 ingest surface as a
 * messages.messages payload under the account's tenancy — plain ingest,
 * deliberately NOT ingestResponse: backfill pages are one-shot history
 * fetches, and the route-dedup key would collapse distinct pages with
 * identical shapes.
 *
 * MEASURED 2026-10-02, CORRECTED 2026-10-03 and AGAIN 2026-10-04 — WRONG THREE
 * TIMES NOW: a peer-scoped write key (FALSE of the code); then
 * `writeMessage()` upserting on `['account_id','id']` against
 * `primary(['account_id','id'])` (200010:211) (also FALSE of the DDL, widened
 * 2026-10-03); then the defect being merely "deferred" on an existing DB.
 * Correct now: the key IS peer-scoped, but only on a FRESHLY CREATED table.
 * On an older DB the peer columns are ABSENT, so the mirror STOPS WRITING
 * LOUDLY — `UpdateIngestor:409`, SQLSTATE 42703 (MEASURED 2026-10-04); the
 * rebuild is still REQUIRED. See the OPERATOR NOTE and UpdateIngestor:395-403.
 *
 * So a multi-peer crawl COLLIDES: measured against this account's 103 real
 * dialogs, a full crawl would address 204,138,975 messages into at most
 * 38,826,516 distinct ids — 80.98% of history destroyed by overwrite, silently,
 * with no clue emitted. The same `(account_id,id)` key is on `tf_messages_service`
 * and all 31 of their child tables, so the parent and its children end up
 * describing DIFFERENT peers, and the search index is keyed the same way.
 *
 * NF5 rests this key on a premise that is factually wrong (spec :94: "duplicates
 * impossible due to disjoint id spaces guaranteed by Telegram" — no such
 * guarantee exists). Until the PK is widened peer-scoped, DO NOT read
 * `SELECT COUNT(*) FROM tf_messages` as a measure of history: its ceiling is
 * max(top_message)+1, forever. Fix is one migration across 35 tables plus one
 * line at `UpdateIngestor:386`; it is the operator's call, not a lane's.
 *
 * --full keeps working the queue until it drains (per-pass budget still
 * halves for live-traffic headroom); per-peer resume cursors are held for
 * the invocation. Durable cross-run cursors remain the Phase 4
 * persistence story (docs/bus.md).
 *
 * DISCOVERY STORES WHAT IT READS. `messages.getDialogs` hands this account
 * the `access_hash` of every peer it can reach, on the page's companion
 * `users` / `chats` vectors — and that page was being read, hashed, turned
 * into fetchable InputPeers, printed, and then DROPPED. A live `--discover`
 * printed real peers and left `tf_channels = 0, tf_users_access_hash = 0,
 * tf_chats = 0`, so every numeric `--peer` stayed refused with
 * `peer_without_access_hash` from the very run that had just held the answer
 * in memory. Each fetched dialogs page therefore now goes through the mirror
 * RESULT route (`dialogsPagePersister()`), whose declared catalog edges are
 * already the ones this page carries. It costs no extra request — the page was
 * already decoded — and `--discover` stays crawl-free, which is the half of
 * its contract that is still a promise: no `messages.getHistory`, no
 * `tf_messages` row.
 *
 * Transports are container seams (mirrors IngestCommand): SCOPE_RESOLVER_KEY
 * binds callable(int $accountId): UserAccountScope, INGESTER_KEY binds
 * callable(int): callable(list<messages>): array — tests swap both.
 */
final class BackfillCommand extends Command
{
    /** Container seam: callable(int $accountId): UserAccountScope (provider binds the live default). */
    public const SCOPE_RESOLVER_KEY = 'teleclient.backfill.scope-resolver';

    /** Container seam: callable(int $accountId): callable(list<array<string, mixed>>): array<string, mixed>. */
    public const INGESTER_KEY = 'teleclient.backfill.ingester';

    /**
     * Container seam: callable(int $accountId): CursorStoreContract (Cycle 17).
     * The durable per-peer cursor + attempt ledger, so a throttled crawl
     * RESUMES instead of restarting every peer at offset 0 and never reaching
     * message id 1. Unbound is a LOUD failure, never a silent fallback to a
     * fresh crawl: --no-persist is the explicit opt-out.
     */
    public const CURSOR_STORE_KEY = 'teleclient.backfill.cursor-store';

    /**
     * The NAMED reason a bare `--peer=<id>` is refused, and the string the
     * operator greps for. It exists because the alternative was silent: the
     * bare id reached `messages.getHistory` with no access_hash, Telegram
     * answered CHANNEL_INVALID/USER_ID_INVALID, and every such run reported
     * the same opaque `fetch_failed` — so a run that could never have
     * succeeded looked identical to a run that hit a rate limit.
     */
    public const PEER_UNRESOLVED = 'peer_without_access_hash';

    /**
     * The ACCOUNT-SCOPED collection methods the backfill run dials, in call
     * order. Exact names, allow-list shape, and the reason each is here is in
     * mirrorAccountCollections()'s docblock; the list is deliberately NOT read
     * from config, because admission is `MirrorResultPolicy`'s decision — a
     * name this loop no longer carries simply stops being dialled, and a name it
     * carries but the policy refuses is a no-op write, not a policy bypass.
     *
     * @var list<string>
     */
    private const ACCOUNT_COLLECTION_METHODS = [
        'payments.getSavedStarGifts',
        'payments.getStarsStatus',
        'account.getBusinessChatLinks',
        'messages.getSavedDialogs',
    ];

    /**
     * The page size every collection fetch below asks for. One constant, not
     * four: these are all "give me the first page of a list the account owns",
     * and a backfill run must cost a bounded, predictable number of requests.
     * Read through `configInt()` so an operator can widen or narrow it with
     * `teleframe.backfill.collection_limit` (a missing key is the default).
     */
    private const COLLECTION_LIMIT = 100;

    /**
     * BARE wire constructor of a dialogs page => the QUALIFIED spelling that
     * `mirror-catalog.json` keys its declared edges under, for the two ctors
     * `messages.getDialogs` can answer with.
     *
     * Both spellings of both ctors are listed, which is what makes the lookup
     * IDEMPOTENT: a page that already arrives qualified — what every fixture
     * and every alternate decoding does — resolves to itself instead of being
     * prefixed a second time or dropped for not matching.
     *
     * This is a MAP of the ctors this command fetches, not a "prefix anything
     * unprefixed with `messages.`" rule. An unknown or empty constructor stays
     * unknown, so the caller keeps skipping it exactly as it did before.
     *
     * @var array<string, string>
     */
    private const DIALOGS_CTOR_BY_WIRE_TYPE = [
        'dialogs' => 'messages.dialogs',
        'messages.dialogs' => 'messages.dialogs',
        'dialogsSlice' => 'messages.dialogsSlice',
        'messages.dialogsSlice' => 'messages.dialogsSlice',
    ];

    protected $signature = 'teleframe:backfill
        {--account= : Telegram account id (tenant) the history lands under}
        {--peer=* : Peer to backfill (repeatable: "@username", numeric id, "-100..."). A NUMERIC id is resolved to the access_hash the account already holds in the mirror, or refused before any request is spent — prefer --full}
        {--full : Discover EVERY dialog and backfill each down to message id 1}
        {--discover : Enumerate and print every dialog, then stop — no crawl, no message read. The access_hashes each page carries ARE stored (tf_dialogs / tf_chats / tf_users_access_hash), so a later --peer=<id> can resolve}
        {--state : Print the account update state (updates.getState) first}
        {--budget=25 : Per-run request budget — the headroom rule halves it}
        {--no-persist : Keep cursors in memory only (explicit opt-out: progress is LOST on exit)}
        {--restart : Forget every durable cursor for this account and crawl from offset 0 again}';

    protected $description = 'Quota-aware history backfill into the P2 truth (fetch queue + offset cursor per peer)';

    protected $aliases = [];

    public function handle(): int
    {
        $accountId = (int) $this->option('account');
        $full = (bool) $this->option('full');
        $discoverOnly = (bool) $this->option('discover');

        $peers = array_values(array_filter(array_map(
            static fn ($peer): string => trim((string) $peer),
            (array) $this->option('peer'),
        )));

        if ($accountId <= 0 || ($peers === [] && ! $full && ! $discoverOnly)) {
            $this->error('Both --account=<id> and --peer=<peer> are required (or pass --full/--discover).');

            return self::FAILURE;
        }

        /**
         * @var array<string, int|string|array<string, mixed>> $inputPeers peer key => getHistory peer argument
         */
        $inputPeers = [];
        /** @var list<string> $keys */
        $keys = $peers;

        // THE ACCEPTANCE BLOCKER, resolved BEFORE the run spends a single
        // request. `--peer=<id>` is a documented form (:94) and was collected
        // verbatim, so a bare id flowed to the wire with no access_hash and
        // Telegram rejected it — five attempts, zero messages, forever. Each
        // unresolvable peer is now REFUSED with a named, actionable reason and
        // dropped from the queue; a run with nothing left to crawl fails here
        // rather than after the account-profile fetch has already cost five
        // requests proving nothing.
        $peerForId = $this->mirrorPeerLookup($accountId);
        $refused = [];

        foreach ($peers as $peer) {
            try {
                $inputPeers[$peer] = self::resolvePeerArgument($peer, $peerForId);
            } catch (RuntimeException $e) {
                $refused[] = $peer;
                $this->error($e->getMessage());
            }
        }

        if ($refused !== []) {
            $keys = array_values(array_diff($keys, $refused));

            if ($keys === [] && ! $full && ! $discoverOnly) {
                $this->error(sprintf(
                    '%s: no crawlable peer remains (%d refused) — nothing to do.',
                    self::PEER_UNRESOLVED,
                    count($refused),
                ));

                return self::FAILURE;
            }
        }

        $scope = $this->resolveScope()($accountId);

        $this->mirrorAccountProfile($accountId, $scope);
        $this->mirrorAccountCollections($accountId, $scope);

        if ((bool) $this->option('state')) {
            $this->reportState($scope);
        }

        if ($full || $discoverOnly) {
            $persistPage = $this->dialogsPagePersister($accountId);

            $discovery = new DialogDiscovery;
            $discovered = $discovery->discover(
                function (int $offsetDate, int $offsetId, array $offsetPeer, int $limit) use ($scope, $persistPage): array {
                    $page = $this->fetchDialogsPage($scope, $offsetDate, $offsetId, $offsetPeer, $limit);

                    $persistPage($page);

                    return $page;
                },
                function (array $entry): void {
                    $this->line(sprintf(
                        'discovered %s {%d:%d, top_message:%d}',
                        $entry['key'],
                        $entry['peer_type'],
                        $entry['peer_id'],
                        $entry['top_message'],
                    ));
                },
            );

            foreach ($discovered as $entry) {
                $inputPeers[$entry['key']] = $entry['peer'];
                $keys[] = $entry['key'];
            }

            $this->info(sprintf('discovered %d dialog(s) for account=%d', count($discovered), $accountId));

            if ($discoverOnly && ! $full) {
                return self::SUCCESS; // preview only: the peers are printed and their hashes stored, but nothing is crawled
            }
        }

        if ($keys === []) {
            $this->error('No peers to backfill (no --peer given and discovery found none).');

            return self::FAILURE;
        }

        return $this->runQueue($accountId, $keys, $inputPeers, $scope, $full);
    }

    /**
     * Turn one `--peer` argument into the peer `messages.getHistory` is
     * allowed to receive — or refuse it, by name, before a request is spent.
     *
     * WHY THE COLLECTOR, NOT `UserAccountScope::normalizePeer()`: that method
     * is `protected`, synchronous, and on the send path of NINE methods
     * (getHistory among them). A hidden RPC inside it would fire an
     * un-requestable lookup on every call of every one of them, so the
     * resolution lives here — where the operator typed the id, where the
     * account's mirror rows are addressable, and where refusing is free
     * because nothing has been dialled yet.
     *
     * THE THREE SHAPES:
     *  - an ARRAY peer is already constructed and already correct (that is
     *    exactly what `--full` produces at :153 and what DialogDiscovery
     *    builds), so it is returned BYTE-IDENTICAL and the lookup is never
     *    consulted. This is the additive guarantee: the new step can only
     *    ever leave a resolved peer alone, never rewrite a good one.
     *  - a NUMERIC id carries no hash of its own and MUST be resolved. It does NOT
     *    carry its peer TYPE either, and this method deliberately does not try to
     *    work it out: `mirrorPeerLookup()` owns identity, reads it from
     *    `tf_dialogs.peer_type`, and this method only decides whether the answer
     *    it got is usable. It used to say, in this docblock, that "negative =
     *    channel/broadcast, positive = user or basic group" — that was the SAME
     *    false premise `mirrorPeerLookup()` was acting on, stated in a second
     *    place, where it would have been found by the next reader to trust it.
     *    There is no logic here to unify: the knowledge lives in exactly one
     *    place, and this paragraph now points at it instead of repeating it.
     *  - anything else (a bare `@username`, for instance) is returned
     *    unchanged. That form is still unresolvable without an extra RPC and
     *    is deliberately out of scope here; what is NOT out of scope is that
     *    it is not silently rewritten into something wrong.
     *
     * REFUSAL IS NOT A FALLBACK — it is the outcome that makes the failure
     * legible. Dialling a hashless peer spends up to MAX_ATTEMPTS requests to
     * learn something this process already knows, and reports it as
     * `fetch_failed`, the same string every other rejection produces.
     *
     * @param  callable(int $peerId): (array<string, mixed>|null)  $peerForId
     *                                                                         the account's mirror index; null means "this account holds no
     *                                                                         usable hash for that id"
     * @return int|string|array<string, mixed>
     *
     * @throws RuntimeException named self::PEER_UNRESOLVED, with the action
     *                          that would have worked
     */
    public static function resolvePeerArgument(int|string|array $peer, callable $peerForId): int|string|array
    {
        if (is_array($peer)) {
            return $peer; // already constructed: never re-derived, never touched
        }

        // `false` from FILTER_VALIDATE_INT casts to 0, and 0 is a safe "not an id"
        // sentinel: `PeerShapeTool::normalize()` and `DialogDiscovery::entry()`
        // both treat peer id 0 as unusable, so no real peer can collide with it.
        $peerId = is_int($peer) ? $peer : (int) filter_var($peer, FILTER_VALIDATE_INT);

        if ($peerId === 0) {
            return $peer; // not a numeric id (@username, a label): untouched
        }

        $resolved = $peerForId($peerId);

        if (! is_array($resolved) || (string) ($resolved['_'] ?? '') === '') {
            throw new RuntimeException(sprintf(
                '%s: --peer=%d cannot be crawled — this account holds no access_hash for peer id %d in '
                .'tf_chats_access_hash / tf_users_access_hash / tf_chats, and Telegram rejects a hashless peer '
                .'(CHANNEL_INVALID / USER_ID_INVALID). No request was spent. Crawl it with `--full`, which '
                .'discovers dialogs that already carry their hashes (:153), or run `--discover` first to see '
                .'the peer ids this account can actually reach.',
                self::PEER_UNRESOLVED,
                $peerId,
                $peerId,
            ));
        }

        return $resolved;
    }

    /**
     * The account's own mirror index of peer id → a fetchable inputPeer, read
     * from the SHIPPED columns dialog discovery already fills. No network, no
     * extra request: `tf_chats_access_hash.access_hash` and
     * `tf_users_access_hash.access_hash` are written by every dialogs page the
     * mirror has ever seen, so for any peer the account has actually met, the
     * hash is already in the database.
     *
     * The three shapes mirror `DialogDiscovery::entry()` exactly, because that
     * is the construction proven to work on the wire, and the peer_type each
     * shape answers for is the one `tf_dialogs` already stores beside it:
     *
     *  - `peer_type = 3` (channel) → `inputPeerChannel` + hash from
     *    `tf_chats_access_hash`.
     *  - `peer_type = 1` (user) → `inputPeerUser` + hash from
     *    `tf_users_access_hash`.
     *  - `peer_type = 2` (chat), or no dialog row but a `tf_chats` row whose
     *    CONSTRUCTOR is `chat` → `inputPeerChat`. A basic group needs NO hash by
     *    design — that is the wire shape, not a gap — so this case is
     *    resolvable, and refusing it would be wrong.
     *
     * IDENTITY IS `peer_type`, NEVER THE SIGN — measured 2026-10-02 against
     * the operator's own account. This used to branch on `$peerId < 0`, on the
     * premise that a negative id is a channel. `-100…` is the BOT API's
     * spelling; MTProto channel ids are POSITIVE, and `DialogDiscovery` stores
     * and prints them that way (`discovered channel:1232026269 {3:1232026269,
     * …}`, `tf_dialogs.peer_id = 1232026269`). So that branch was dead code: a
     * real channel id fell through to the user table, missed, and the run
     * refused with `peer_without_access_hash` — naming `tf_chats_access_hash`
     * as a place to look while 127 real rows sat in it.
     *
     * A row that EXISTS with a zero/empty hash is NOT resolvable and must not
     * fall through to the chat branch: a user fact row with no hash means the
     * hash is unknown, and `inputPeerChat` would then name a different peer
     * entirely.
     *
     * THE CHAT BRANCH IS CONSTRUCTOR-GATED, and it has to be. It used to read
     * a bare `tf_chats` row as "this is a basic group", which was true only
     * while that table was never written by anything. It now is: a persisted
     * `messages.getDialogs` page puts every peer there, CHANNELS INCLUDED —
     * the catalog's `tf_chats` entry admits the `channel` and `channelForbidden`
     * constructors alongside `chat`, so one row of `constructor = 'channel'` is
     * a MEGAGROUP that needs an `access_hash`, not a basic group that does not.
     * Answering `inputPeerChat` for it would name a DIFFERENT PEER on the wire
     * and get `CHAT_ID_INVALID` back — a wrong-peer request bought with a
     * request the run had budget for. Only `chat` is a basic group
     * (TL_telegram_v227.tl `chat#41fbf7e5`); `chatEmpty` and `chatForbidden`
     * carry no usable peer either, so every non-`chat` constructor stays the
     * honest refusal it was before.
     *
     * Scoped by `account_id` throughout: an access_hash is per-account state,
     * and reading another tenant's would be reading another account's data.
     *
     * @return callable(int): (array<string, mixed>|null)
     */
    private function mirrorPeerLookup(int $accountId): callable
    {
        $db = $this->getLaravel()->make('db')->connection();

        /*
         * NAMED HELPER — Bot-API spelling to MTProto id.
         *
         * An operator types a supergroup the way the Bot API spells it,
         * `-1001232026269`. Telegram's own ids are POSITIVE, and that is the
         * only reason this normalisation is safe: a negative int cannot be an
         * MTProto peer id at all, so the only thing it can be is the borrowed
         * prefix. Anything that is not `-100` followed by digits is returned
         * UNCHANGED rather than mangled, so the caller still gets the honest
         * refusal instead of a fabricated id that happens to match a real peer.
         *
         * It is a separate named helper on purpose: this used to be implicit in
         * `$peerId < 0`, i.e. the same arithmetic that used to decide WHICH
         * TABLE to read. One helper, one job — "what id does the wire call
         * this peer" — and it no longer decides what the peer IS.
         */
        $mtprotoPeerId = static function (int $rawId): int {
            if ($rawId >= 0) {
                return $rawId;
            }

            $digits = substr((string) $rawId, 4); // strip '-100'

            if ($rawId < -1000000000 && $digits !== '' && ctype_digit($digits)) {
                return (int) $digits;
            }

            return $rawId;
        };

        /*
         * NAMED HELPER — WHICH KIND OF PEER IS THIS ID?
         *
         * Returns 'user' | 'channel' | 'chat' | '' (unknown ⇒ honest refusal).
         *
         * THE SIGN IS NOT THE DISCRIMINATOR, and this is the whole fix.
         * `mirrorPeerLookup()` used to read `$peerId < 0` and call a negative id
         * a channel. That premise is false on MTProto: `-100…` is the BOT API's
         * spelling, and `DialogDiscovery` stores and prints channels POSITIVE
         * (`discovered channel:1232026269 {3:1232026269, …}`, `tf_dialogs.peer_id
         * = 1232026269`). So `$peerId < 0` was never true for a channel this
         * command produces: the channel branch was dead code, a real channel id
         * fell through to the user table, missed, and the run refused a peer
         * whose `access_hash` was sitting in `tf_chats_access_hash` — while the
         * refusal message named that very table as a place to look.
         *
         * `tf_dialogs` already records the answer per account, at zero request
         * cost: it is keyed `(account_id, peer_type, peer_id)`, the same triple
         * `DialogDiscovery::entry()` builds a peer from, and `peer_type` is
         * Telegram's own enumeration (1 user, 2 chat, 3 channel). So the
         * database has already classified this id and the lookup READS that
         * classification instead of re-inferring it from arithmetic.
         *
         * SECOND SOURCE, and why it is needed. A peer need not appear in
         * `tf_dialogs` to be addressable: a basic group the account left long
         * ago can still be named by a `tf_chats` row with constructor `chat`,
         * and that is how this branch resolved before the rewrite. So when no
         * dialog row exists, the CONSTRUCTOR is the identity evidence — a
         * `chat` / `chatEmpty` / `chatForbidden` / `channel` / `channelForbidden`
         * value on `tf_chats` says which of the five constructors this id is.
         * That never lets a user id reach the channel table, because a USER has
         * no `tf_chats` row by construction.
         *
         * ONE EXCEPTION INSIDE IT, and it is load-bearing. A `tf_chats` row
         * saying `chat` is NOT enough on its own when a `tf_users_access_hash`
         * row also exists for the same id: the user row is positive evidence
         * that this id names a user, and a user fact row with an unusable hash
         * means the hash is UNKNOWN — not that the peer is a group. Falling
         * through to `inputPeerChat` there would name a different peer on the
         * wire and buy a CHAT_ID_INVALID with a budgeted request, which is
         * precisely the failure this command refuses rather than spends.
         */
        $peerIdentity = static function (int $peerId) use ($db, $accountId): string {
            $dialog = $db->table('tf_dialogs')
                ->where('account_id', $accountId)
                ->where('peer_id', $peerId)
                ->first(['peer_type']);

            if ($dialog !== null) {
                return match ((int) $dialog->peer_type) {
                    1 => 'user',
                    2 => 'chat',
                    3 => 'channel',
                    default => '',
                };
            }

            $ctor = $db->table('tf_chats')
                ->where('account_id', $accountId)
                ->where('id', $peerId)
                ->value('constructor');

            // The same split the channel hash read had to follow: a CHANNEL's
            // constructor lives on `tf_channels`, not `tf_chats`, so reading
            // `tf_chats` alone leaves a channel with no dialog row unclassifiable
            // — the second source would report `''` for a peer the mirror does
            // hold, and the run would refuse it. `tf_chats` is read FIRST and
            // `tf_channels` only when it says nothing, so the two tables can
            // never disagree about an id in a way that is not visible here:
            // an id is claimed by at most one of them, because the walk routes
            // by each item's OWN constructor.
            if (! is_scalar($ctor)) {
                $ctor = $db->table('tf_channels')
                    ->where('account_id', $accountId)
                    ->where('id', $peerId)
                    ->value('constructor');
            }

            if (! is_scalar($ctor)) {
                return ''; // no dialog row and no peer fact: nothing claims this id
            }

            $hasUserRow = $db->table('tf_users_access_hash')
                ->where('account_id', $accountId)
                ->where('id', $peerId)
                ->exists();

            return match ((string) $ctor) {
                'channel', 'channelForbidden' => 'channel',
                'chat' => $hasUserRow ? '' : 'chat',
                // `chatEmpty` / `chatForbidden` carry no usable peer, so they
                // stay the honest refusal they were before — even with a user
                // row present, which is ambiguous enough to refuse.
                default => '',
            };
        };

        return static function (int $peerId) use ($db, $accountId, $mtprotoPeerId, $peerIdentity): ?array {
            $peerId = $mtprotoPeerId($peerId);

            // Resolved ONCE and dispatched on. Calling the classifier again per
            // branch would re-issue its two or three reads on a path that runs
            // once per peer per run — and, worse, would leave the branch a
            // future edit can quietly disagree with.
            $identity = $peerIdentity($peerId);

            if ($identity === 'channel') {
                // `tf_channels.access_hash`, and the read had to MOVE with the
                // ctor split.
                //
                // This branch used to read `tf_chats_access_hash` on the stated
                // premise (recorded above in this file's own history) that "the
                // catalog routes the `channel` / `channelForbidden`
                // constructors to `tf_chats` … there is no `tf_channels` entry
                // in the catalog at all, and nothing in `src/` writes it, so
                // reading it could only ever find a row nobody puts there."
                //
                // That premise was true when written and is FALSE now. The
                // shipped `Chat` union is split across two tables by design —
                // `tf_chats` holds `chatEmpty`/`chat`/`chatForbidden`,
                // `tf_channels` holds `channel`/`channelForbidden`, because a
                // channel id is NEGATIVE and `tf_channels.id` is the peer FK
                // target for `peer_type 3`
                // (`2026_09_14_200002_create_tf_chats_tables.php:13-15`,
                // `2026_09_14_200003_create_tf_channels_tables.php:12-14`,
                // `Naming::CTOR_DOMAIN_OVERRIDES` in
                // `src/Schema/Generator/Naming.php:131-133`), and the catalog
                // now agrees: `tf_chats.ctors` is the three chat ctors and
                // `tf_channels.ctors` is `["channel","channelForbidden"]`.
                // `MirrorNestedSourceWalker::tableFor()` routes each item of a
                // `chats` VECTOR by its OWN constructor, so a dialogs page
                // writes the channel to `tf_channels` and NOTHING to
                // `tf_chats_access_hash`.
                //
                // MEASURED consequence of leaving the read where it was: one
                // `messages.getDialogs` page carrying one `channel` (id
                // 1915503741, access_hash 9172635481029) and one basic `chat`
                // produced `tf_channels` = 1 row carrying the hash,
                // `tf_chats` = 1 row carrying only the `chat`, and
                // `tf_chats_access_hash` = 0 rows. `--discover` then REFUSED
                // `--peer=1915503741` with `peer_without_access_hash` and exit
                // code 1 — refusing a peer whose hash the mirror had stored,
                // which is the exact defect this lookup exists to prevent.
                //
                // `access_hash` is a BASE column on `tf_channels`, not a 1:1
                // child: the shipped DDL types it inline
                // (`2026_09_14_200003_create_tf_channels_tables.php:56`), and
                // the catalog agrees (`tf_channels.base` carries
                // `["access_hash","BIGINT NOT NULL"]`). So this reads the parent
                // row, and `usableHash()` still rejects the 0 default that
                // means "Telegram has not told us".
                $row = $db->table('tf_channels')
                    ->where('account_id', $accountId)
                    ->where('id', $peerId)
                    ->first(['id', 'access_hash']);

                $hash = $row === null ? null : self::usableHash($row->access_hash ?? null);

                return $hash === null
                    ? null
                    : ['_' => 'inputPeerChannel', 'channel_id' => $peerId, 'access_hash' => $hash];
            }

            // A USER is answered from the USER table and from nothing else.
            // The `match` on identity rather than a fallback chain is the whole
            // point: reaching here already means this id is not a channel, so
            // the channel table is not consulted even if it happens to hold a
            // row for the same number.
            if ($identity === 'user') {
                $user = $db->table('tf_users_access_hash')
                    ->where('account_id', $accountId)
                    ->where('id', $peerId)
                    ->first(['id', 'access_hash']);

                $hash = $user === null ? null : self::usableHash($user->access_hash ?? null);

                return $hash === null
                    ? null
                    : ['_' => 'inputPeerUser', 'user_id' => $peerId, 'access_hash' => $hash];
            }

            // A BASIC GROUP is the last branch, and it is gated TWICE on purpose.
            //
            //  - `$identity === 'chat'` — the classifier already refused to call
            //    this a group when a `tf_users_access_hash` row claims the same
            //    id, and that refusal must not be re-decided here in the weaker
            //    direction. Without this gate a user row with an unusable hash
            //    falls straight through to `inputPeerChat` and names a DIFFERENT
            //    PEER on the wire.
            //  - the CONSTRUCTOR — `tf_chats` carries the basic group `chat` AND
            //    every channel (`channel`, `channelForbidden`), and only the
            //    first is addressable as an inputPeerChat. The gate is
            //    re-asserted at the point of use as well as at classification,
            //    because a gate enforced in only one of the two places is a gate
            //    a future edit can route around.
            if ($identity === 'chat') {
                $chat = $db->table('tf_chats')
                    ->where('account_id', $accountId)
                    ->where('id', $peerId)
                    ->first(['id', 'constructor']);

                return $chat !== null && (string) $chat->constructor === 'chat'
                    ? ['_' => 'inputPeerChat', 'chat_id' => $peerId]
                    : null;
            }

            // Unknown identity, or a channel/user whose hash is unusable: the
            // honest refusal. Nothing has been dialled to learn this.
            return null;
        };
    }

    /**
     * A hash is usable when it is present AND non-zero. Both mirror columns
     * are `bigInteger` with a 0 default, and 0 is exactly the value that
     * means "Telegram has not told us this peer's hash" — sending it is worse
     * than sending nothing, because it looks resolved.
     */
    private static function usableHash(mixed $value): ?string
    {
        $hash = is_scalar($value) ? trim((string) $value) : '';

        return ($hash === '' || $hash === '0') ? null : $hash;
    }

    /**
     * Drive the FetchQueue through BackfillWorker.
     *
     * Default mode stops at the first per-run budget exhaustion (v1
     * behaviour: the peer stays queued, resume by re-running). --full keeps
     * draining: a peer at budget is moved to the back with its resume cursor
     * held in $cursors, so every peer progresses until the queue empties.
     *
     * @param  list<string>  $keys
     * @param  array<string, int|string|array<string, mixed>>  $inputPeers
     */
    private function runQueue(int $accountId, array $keys, array $inputPeers, UserAccountScope $scope, bool $full): int
    {
        // Cycle 17: resolve the durable ledger FIRST, and drop peers it already
        // marks terminal BEFORE they reach the queue. Filtering after the queue
        // is built would still crawl them (the queue would pop them), which is
        // the bug the terminal filter must prevent.
        $store = $this->resolveCursorStore($accountId);
        $terminal = $store->terminalPeers($accountId);

        if ($this->option('restart')) {
            foreach (array_keys($terminal) as $peerKey) {
                $store->forget($accountId, (string) $peerKey);
            }
            $terminal = [];
        }

        $queue = new FetchQueue;
        $queued = 0;
        foreach ($keys as $key) {
            if (isset($terminal[$key])) {
                continue; // complete or dead in the ledger: never re-crawled
            }
            $queue->push($key);
            $queued++;
        }

        if ($this->option('restart')) {
            $this->info('--restart: durable cursors forgotten; every peer walks from offset 0.');
        }

        if ($queued === 0) {
            $this->info(sprintf(
                'backfill account=%d — all %d peer(s) already terminal in the cursor ledger (nothing to crawl)',
                $accountId,
                count($terminal),
            ));

            return 0;
        }

        // Restore cross-run cursors and attempt counts for the queued peers.
        /** @var array<string, int> peer => resume offset_id */
        $cursors = [];
        foreach ($store->cursorsFor($accountId) as $peerKey => $row) {
            $peerKey = (string) $peerKey;
            if (isset($terminal[$peerKey])) {
                continue; // finished or exhausted: never re-crawled silently
            }

            $cursors[$peerKey] = (int) ($row['offset_id'] ?? 0);
            $queue->seed($peerKey, (int) ($row['attempts'] ?? 0), (string) ($row['reason'] ?? ''));
        }

        if ($this->option('restart')) {
            $cursors = []; // rows were already forgotten above; crawl from 0
        }

        $worker = new BackfillWorker(
            max(1, (int) ($this->option('budget') ?: $this->configInt('request_budget', 25))),
            max(1, $this->configInt('flood_cap_seconds', 3600)),
        );

        $ingester = $this->resolveIngester()($accountId);

        /**
         * @var list<string> $ingestFailures one line per batch the writer
         *                   REFUSED to store, carrying its own reason. The default writer
         *                   reports `['stored' => 0, 'reason' => …]` when a batch throws;
         *                   that value used to be discarded here (`$ingester($batch);`),
         *                   so a run that fetched pages and wrote NOTHING still exited 0
         *                   with "messages:N" in its summary — which is precisely how
         *                   `SELECT COUNT(*) FROM tf_messages` stayed 0 with no error
         *                   anywhere.
         */
        $ingestFailures = [];

        $messages = 0;
        $requests = 0;
        $done = 0;
        $dead = 0;
        $deadPeers = [];
        $status = 'empty';
        $sweeps = 0;

        while (true) {
            $peer = $queue->pop();

            if ($peer === null) {
                break;
            }

            if (isset($deadPeers[$peer])) {
                $queue->complete($peer); // stalled/dead this run: future-run material only

                continue;
            }

            $target = $inputPeers[$peer] ?? $peer;
            $start = $cursors[$peer] ?? 0;

            $result = $worker->process(
                $peer,
                fn (int $offsetId, int $limit): array => $this->fetchPage($scope, $target, $offsetId, $limit),
                static function (array $batch) use ($ingester, &$ingestFailures): void {
                    // The worker's onBatch is `void`, so the writer's report is
                    // read HERE and carried out in $ingestFailures rather than
                    // returned — otherwise the only signal that a page was
                    // never persisted dies at this call boundary. The seam's
                    // declared type is `array<string, mixed>`, so `reason` is
                    // always addressable once the writer publishes it.
                    $report = $ingester($batch);
                    $reason = trim((string) ($report['reason'] ?? ''));

                    if ($reason !== '') {
                        $ingestFailures[] = sprintf('%d message(s): %s', count($batch), $reason);
                    }
                },
                $start,
            );

            $messages += $result['messages'];
            $requests += $result['requests'];
            $status = $result['status'];
            $cursors[$peer] = $result['offset_id'];

            // The reason is PART of the line, not a debug channel: this line is the whole
            // diagnosis an operator gets for a dead peer, and it used to print
            // `status:dead` alone — five identical lines for a peer Telegram
            // could not resolve. Printed only when there IS one, so a clean
            // `status:done` line stays clean.
            $reason = (string) ($result['reason'] ?? '');

            $this->line(sprintf(
                '%s {messages:%d, requests:%d, offset_id:%d, status:%s%s}',
                $peer,
                $result['messages'],
                $result['requests'],
                $result['offset_id'],
                $result['status'],
                $reason === '' ? '' : ', reason:'.$reason,
            ));

            if ($result['status'] === 'done') {
                $queue->complete($peer);
                $store->upsertCursor($accountId, [
                    'peer_key' => $peer,
                    'offset_id' => $result['offset_id'],
                    'attempts' => 0,
                    'status' => CursorStoreContract::STATUS_COMPLETE,
                    'reason' => '',
                    'last_offset_at_unix' => time(),
                ]);
                $done++;

                continue;
            }

            if ($result['status'] === 'dead') {
                // attempts++ for the cross-run ledger — dead-lettered after
                // 5 runs (FetchQueue::MAX_ATTEMPTS); this run moves on.
                $queue->fail($peer, (string) $result['reason']);
                $store->upsertCursor($accountId, [
                    'peer_key' => $peer,
                    'offset_id' => $result['offset_id'],
                    // Read the queue's own count: the run may have been seeded
                    // with prior attempts, so $attempts++ from the local var
                    // would reset a peer at 1 every invocation.
                    'attempts' => $queue->attemptsFor($peer),
                    'status' => CursorStoreContract::STATUS_DEAD,
                    'reason' => (string) $result['reason'],
                    'last_offset_at_unix' => time(),
                ]);
                $deadPeers[$peer] = true;
                $dead++;

                continue;
            }

            // status === 'budget'
            if (! $full) {
                $queue->requeue($peer);
                // Cycle 17: a budget stop is the NORMAL case, not an error —
                // persist the cursor now so the NEXT invocation resumes here
                // instead of re-fetching this whole page from offset 0.
                $store->upsertCursor($accountId, [
                    'peer_key' => $peer,
                    'offset_id' => $result['offset_id'],
                    'attempts' => $queue->attemptsFor($peer),
                    'status' => CursorStoreContract::STATUS_ACTIVE,
                    'reason' => '',
                    'last_offset_at_unix' => time(),
                ]);

                break; // budget: the peer stays queued at the front; the next run resumes from the persisted offset_id
            }

            // Full mode: move to the back and keep going. A pass that fetched
            // nothing AND did not advance the cursor (server refuses to go
            // deeper) is stalled — complete it rather than spin forever.
            if ($result['messages'] === 0 && $result['offset_id'] === $start) {
                $deadPeers[$peer] = true;
            }

            $queue->requeueBack($peer);

            // Write through on every requeue: --full is the mode that actually
            // gets interrupted by FLOOD_WAIT_27 mid-enumeration, so this is the
            // line that decides whether the crawl can ever reach id 1.
            $store->upsertCursor($accountId, [
                'peer_key' => $peer,
                'offset_id' => $result['offset_id'],
                'attempts' => $queue->attemptsFor($peer),
                'status' => $deadPeers[$peer] ?? false
                    ? CursorStoreContract::STATUS_DEAD
                    : CursorStoreContract::STATUS_ACTIVE,
                'reason' => $deadPeers[$peer] ?? false ? 'stalled-no-progress' : '',
                'last_offset_at_unix' => time(),
            ]);

            if (++$sweeps > 1_000_000) {
                break; // absolute guard against a pathological cursor
            }
        }

        $this->info(sprintf(
            'backfill account=%d {messages:%d, requests:%d, status:%s} — %d done, %d dead, %d queued',
            $accountId,
            $messages,
            $requests,
            $status,
            $done,
            $dead,
            $queue->counts()['pending'],
        ));

        if ($ingestFailures !== []) {
            // A fetched page that was never written is a failed run, not a
            // quiet one. Reported per-batch and failed loudly, because the
            // operator's criterion is `SELECT COUNT(*) FROM tf_messages` and
            // "messages:N" in the summary counts FETCHED, not stored.
            $this->error(sprintf(
                'ingest: %d batch(es) were NOT stored — %s',
                count($ingestFailures),
                implode(' | ', array_slice($ingestFailures, 0, 3)),
            ));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function reportState(UserAccountScope $scope): void
    {
        $state = $scope->getState();

        $this->info(sprintf(
            'state {pts:%d, qts:%d, date:%d, seq:%d}',
            (int) ($state['pts'] ?? 0),
            (int) ($state['qts'] ?? 0),
            (int) ($state['date'] ?? 0),
            (int) ($state['seq'] ?? 0),
        ));
    }

    /**
     * Persist every clue of a mirror result/page report to `tf_ingest_clues`.
     *
     * ## WHY THIS IS HERE (fix round 1/5 finding B1)
     *
     * Three sites in this file — `mirrorAccountProfile()`,
     * `mirrorAccountCollections()` and the `dialogsPagePersister()` closure —
     * each read `fkClues`, `unmappedClues` and `valueClues` off a mirror write
     * and did NOTHING BUT `warn()` them. That is a print, not a record: the run
     * that printed them is over, and a coverage gap nobody was watching the
     * terminal for at that moment is gone for good.
     *
     * The backfill is the WORST place for that, because it is where the volume
     * is: a discovery run places thousands of facts across hundreds of pages and
     * every page that could not place a fact left no trace outside one scrolled
     * terminal buffer. So this path wrote the most rows and accounted for the
     * fewest of them. The three sites above now call this, and the whole report
     * is handed over WHOLE rather than one bucket at a time — a caller that
     * persisted only the bucket it happened to print is the exact defect, so the
     * API is shaped to make it unrepeatable.
     *
     * NON-FATAL BY DESIGN, like `MirrorIngesterFactory::recordClues()`. A host
     * that has not run the new migration must still be able to crawl history; a
     * ledger that takes the crawl down with it is a worse outage than the gap it
     * documents. The failure is surfaced as a command warning rather than
     * swallowed, so the host learns its ledger is not working — and the two
     * surfaces now behave the same way, which they did not before this call.
     *
     * @param  array<string, mixed>  $report
     */
    private function recordClues(int $accountId, array $report): void
    {
        try {
            TfIngestClue::recordReport(DB::connection(), $report, $accountId);
        } catch (QueryException $e) {
            $this->components->warn(sprintf(
                'the ingest clue ledger could not be written for account=%d (%s) — the mirror continues, '
                .'but this run\'s coverage gaps are NOT recorded',
                $accountId,
                $e->getMessage(),
            ));
        }
    }

    /**
     * Mirror the ACCOUNT'S OWN full profile — the live caller of the
     * RPC-result mirror route.
     *
     * Until this call the route was proven and unreachable: the mirror's only
     * ingest surface was fed by the Redis bus, so it only ever saw updates,
     * and every constructor Telegram delivers solely as an RPC result shipped
     * DDL, a model, a write path and no data. `botInfo` is the cleanest
     * instance — it is never an update's own constructor, it rides
     * `userFull.bot_info` behind flag 3, so `tf_bot_infos` could not fill from
     * any update the account could ever receive.
     *
     * WHY THIS METHOD AND NOT THE CRAWLED PAGES: a `messages.getHistory` page
     * carries no constructor the catalog declares as a nested source, so
     * routing it would be a call that provably writes nothing — a wiring that
     * changes no behaviour, which is the defect being closed. The account's
     * OWN full user is the one full-user shape Telegram hands over without an
     * id (`inputUserSelf`), it is the row the account owns and the mirror has
     * no other source for, and it costs exactly one request per run.
     *
     * WHY IT IS SAFE TO BE AWAITED HERE: the write is keyed on the shipped
     * `(account_id, id)` primary key, so a re-run overwrites rather than
     * duplicates, and it runs before the crawl so a throttled or failing
     * profile fetch costs one request, not the run.
     *
     * IT IS OPT-IN BY CONSTRUCTION, twice over. The operator ran the command
     * at all, and the call only happens for a method
     * `MirrorResultPolicy` admits — an operator who publishes
     * `teleframe.mirror.result_methods = []` turns it off without touching
     * this file. A failure is reported and the crawl proceeds: an auxiliary
     * profile fetch must not take the history down with it.
     */
    private function mirrorAccountProfile(int $accountId, UserAccountScope $scope): void
    {
        $mirror = MirrorIngesterFactory::buildResultMirror();

        try {
            $result = $scope->getFullUser(InputUser::self());
        } catch (Throwable $e) {
            $this->warn(sprintf(
                'mirror: users.getFullUser failed for account=%d (%s) — history crawl continues',
                $accountId,
                $e->getMessage(),
            ));

            return;
        }

        $written = $mirror('users.getFullUser', $result, $accountId);

        // LABEL CORRECTED 2026-10-04 on all three mirror log lines in this file:
        // `inserted` counts STATEMENTS PLACED, never rows held. Measured on the
        // shipped DDL, three facts colliding on one primary key print 3 and the
        // table holds one row, every clue bucket empty — so a log line reading
        // `inserted:537` told an operator 537 rows were stored when the mirror may
        // hold fewer. The number is unchanged and nothing here gates on it; the
        // clue buckets printed beneath are what the operator acts on. Same
        // correction as `teleframe:ingest:gate`, whose row is now labelled
        // `statements placed`.
        if ($written['mirrored'] && $written['inserted'] > 0) {
            $this->line(sprintf('mirror account=%d {method:users.getFullUser, statements:%d}', $accountId, $written['inserted']));
        }

        foreach ($written['fkClues'] as $clue) {
            $this->components->warn("mirror FK clue: {$clue}");
        }
        foreach ($written['unmappedClues'] as $clue) {
            $this->components->warn("mirror unmapped field: {$clue}");
        }
        // Own label, never folded into the FK list: a value fault is the VALUE
        // not fitting the column the DDL gives it (MySQL 1265/1364/1366/1406,
        // Postgres 22001) — a column-shape problem, not a relationship one.
        foreach ($written['valueClues'] as $clue) {
            $this->components->warn("mirror value clue: {$clue}");
        }

        $this->recordClues($accountId, $written);
    }

    /**
     * Dial the ACCOUNT-SCOPED collections the account owns and nobody else can
     * fetch for it: the saved star gifts, the stars balance with its
     * transaction and subscription history, the business chat links, the saved
     * dialogs, and the installed sticker sets.
     *
     * WHY IT SITS BESIDE mirrorAccountProfile() AND NOT INSIDE IT: the profile
     * fetch is ONE call with a name the method registry exposes as a typed
     * method (`getFullUser(inputUserSelf)`), so it needs no param assembly.
     * These four are dialled through the generic `UserAccountScope::call()`
     * seam, and three of them DO need param assembly — see
     * accountCollectionParams() for the per-method argument set, which is
     * read off the shipped catalog, not guessed. Pagination beyond that first
     * page is deliberately NOT driven from here: a backfill run must cost a
     * bounded, predictable number of requests, and walking a whole collection
     * would be unbounded.
     *
     * WHY EVERY ARGUMENT HERE IS THE ACCOUNT'S OWN, NEVER A PLACEHOLDER: each
     * required `InputPeer` is `inputPeerSelf`, which is the documented value
     * for "the account asking about itself" (payments.getStarsStatus's own
     * description names inputPeerSelf for the current account's balance), and
     * each pagination cursor is the "start from the beginning" value its type
     * demands. A wrong-but-nonempty peer here would silently mirror ANOTHER
     * account's data, so a peer the command cannot prove is self is never
     * invented — see accountCollectionParams(), which names that per method.
     *
     * WHY A FAILURE WARNS AND CONTINUES: a server that refuses a payload (an
     * account with no business connection, a method it does not accept)
     * answers with an RPC error, which surfaces as a throwable — hence the
     * PER-METHOD try/catch below rather than one around the loop. A method
     * that fails warns and is skipped; it never takes the other three, and
     * never takes the history crawl, down with it.
     *
     * ONE mirror callable, built once and reused for all four: the callable is
     * the policy-gated route, and building it per method would re-resolve the
     * operator's `teleframe.mirror.result_methods` override four times for no
     * gain.
     *
     * IT IS OPT-IN BY CONSTRUCTION, exactly as the profile fetch is. The
     * admission list is NOT read from config here — that is
     * `MirrorResultPolicy`'s job, and the route is a no-op for any method the
     * policy does not admit, so an operator who narrows
     * `teleframe.mirror.result_methods` turns any of these four off without
     * touching this file, and a name dropped from the policy simply stops
     * writing.
     *
     * Provenance: the four names are the admitted defaults
     * `MirrorResultPolicy::DEFAULT_METHODS` names for the account-scoped
     * collections — payments.getSavedStarGifts, payments.getStarsStatus,
     * account.getBusinessChatLinks, messages.getSavedDialogs — each verified
     * present in src/Schema/schema/methods-mtproto.json and cross-checked
     * against schema/sources/TL_telegram_v227.tl. The write path is the same
     * policy-gated `MirrorIngesterFactory::buildResultMirror()` route the
     * profile fetch uses, so the catalog's `sources` edge is still the second,
     * independent gate on what actually lands.
     */
    private function mirrorAccountCollections(int $accountId, UserAccountScope $scope): void
    {
        $mirror = MirrorIngesterFactory::buildResultMirror();

        foreach (self::ACCOUNT_COLLECTION_METHODS as $method) {
            $params = $this->accountCollectionParams($method);

            try {
                $result = $scope->call($method, $params);
            } catch (Throwable $e) {
                $this->warn(sprintf(
                    'mirror: %s failed for account=%d (%s) — history crawl continues',
                    $method,
                    $accountId,
                    $e->getMessage(),
                ));

                continue;
            }

            $written = $mirror($method, $result, $accountId);

            // `statements`, not `inserted` — see the label correction above.
            if ($written['mirrored'] && $written['inserted'] > 0) {
                $this->line(sprintf('mirror account=%d {method:%s, statements:%d}', $accountId, $method, $written['inserted']));
            }

            foreach ($written['fkClues'] as $clue) {
                $this->components->warn("mirror FK clue: {$clue}");
            }
            foreach ($written['unmappedClues'] as $clue) {
                $this->components->warn("mirror unmapped field: {$clue}");
            }
            // Own label, never folded into the FK list: a value fault is the
            // VALUE not fitting the column the DDL gives it (MySQL
            // 1265/1364/1366/1406, Postgres 22001) — a column-shape problem,
            // not a relationship one. Empty list = silent, as above.
            foreach ($written['valueClues'] as $clue) {
                $this->components->warn("mirror value clue: {$clue}");
            }

            $this->recordClues($accountId, $written);
        }
    }

    /**
     * The exact argument set each account-scoped collection method is dialled
     * with, read off the shipped catalog and cross-checked against the live TL
     * scheme. An UNKNOWN method returns `[]` and is NOT dialled with a guess:
     * the loop is a constant list, so a name reaching this switch unmatched
     * means the list and this table have drifted, and the `default` arm keeps
     * that failure a no-op rather than a wrong-account write.
     *
     * Only UNFLAGGED params appear below — every `flags.N?` param is optional
     * on the wire and is deliberately omitted, because passing `true` for an
     * exclusion filter (e.g. `exclude_pinned`) would quietly narrow what the
     * collection returns and hide rows that do exist.
     *
     * `peer` is `inputPeerSelf` on all three that need one, and that is a
     * DERIVED value, not a default: it means "the account that made this call"
     * (payments.getStarsStatus's own catalog description says the current
     * account's balance is fetched "with peer=inputPeerSelf"), which is
     * exactly the tenancy these tables are keyed by. A non-empty peer id here
     * would mirror SOMEONE ELSE's stars/gifts into this account's rows, so no
     * such value is ever constructed.
     *
     * Pagination: the pagination cursor params are set to the
     * "start from the beginning" value each type requires — int 0, the empty
     * string for getSavedStarGifts' `offset` (a STRING offset, not an int; the
     * catalog types it `string` and this is where that is honoured), the
     * account's own `inputPeerSelf` where a peer-typed cursor is required, and
     * `hash` 0 (an empty cache hash is the documented first-page value). Only
     * the FIRST page is fetched, by design: a backfill run has a bounded
     * request budget and a whole collection walk does not.
     *
     * `limit` is the one value that is an operator preference rather than a
     * derivation, so it is read from `teleframe.backfill.collection_limit`
     * (falling back to COLLECTION_LIMIT) rather than hard-coded per method.
     *
     * Provenance: parameter names, types and flag words are those of
     * src/Schema/schema/methods-mtproto.json — account.getBusinessChatLinks
     * (line 851, `params: []`), messages.getSavedDialogs (line 18157),
     * payments.getSavedStarGifts (line 26117), payments.getStarsStatus
     * (line 26652) — and match the constructor lines
     * schema/sources/TL_telegram_v227.tl:2370, :2618, :2872, :2846.
     *
     * @return array<string, mixed>
     */
    private function accountCollectionParams(string $method): array
    {
        $limit = $this->configInt('collection_limit', self::COLLECTION_LIMIT);
        $self = ['_' => 'inputPeerSelf'];

        return match ($method) {
            // TL:2370 — `account.getBusinessChatLinks#6f70dde1 = account.BusinessChatLinks;`
            // Genuinely param-free: the catalog lists an EMPTY `params` array.
            'account.getBusinessChatLinks' => [],

            // TL:2872 — `peer:InputPeer offset:string limit:int` are all unflagged.
            // `offset` is a STRING cursor; '' is its first-page value.
            'payments.getSavedStarGifts' => [
                'peer' => $self,
                'offset' => '',
                'limit' => $limit,
            ],

            // TL:2846 — `peer:InputPeer` is the only unflagged param. inputPeerSelf
            // is the account's own balance, per this method's own description.
            'payments.getStarsStatus' => [
                'peer' => $self,
            ],

            // TL:2618 — `offset_date:int offset_id:int offset_peer:InputPeer
            // limit:int hash:long` are all unflagged. The peer-typed cursor gets
            // the same self value: it is the position, not a target account.
            'messages.getSavedDialogs' => [
                'offset_date' => 0,
                'offset_id' => 0,
                'offset_peer' => $self,
                'limit' => $limit,
                'hash' => 0,
            ],

            default => [],
        };
    }

    /**
     * One messages.getHistory page (DESC); the BackfillWorker script owns
     * offset/limit sequencing.
     *
     * @param  int|string|array<string, mixed>  $peer
     * @return list<array<string, mixed>>
     */
    private function fetchPage(UserAccountScope $scope, int|string|array $peer, int $offsetId, int $limit): array
    {
        $response = $scope->getHistory($peer, $limit, $offsetId);

        /** @var list<array<string, mixed>> $messages */
        $messages = $response['messages'] ?? [];

        return $messages;
    }

    /**
     * One messages.getDialogs page, honoring FLOOD_WAIT. Discovery runs
     * OUTSIDE BackfillWorker (it seeds the queue), so it cannot lean on the
     * worker's flood handling: on FLOOD_WAIT_<n> sleep min(n, cap) and retry
     * the SAME cursor page, up to a small attempt cap. Telegram throttles a
     * big dialogs walk (live-observed FLOOD_WAIT_27 mid-enumeration) — this
     * is expected, not an error.
     *
     * @param  array<string, mixed>  $offsetPeer
     * @return array<string, mixed>
     */
    private function fetchDialogsPage(UserAccountScope $scope, int $offsetDate, int $offsetId, array $offsetPeer, int $limit): array
    {
        $cap = max(1, $this->configInt('flood_cap_seconds', 3600));

        for ($attempt = 0; ; $attempt++) {
            try {
                return $scope->getDialogsPage($limit, $offsetDate, $offsetId, $offsetPeer);
            } catch (FloodWaitException $e) {
                if ($attempt >= 4) {
                    throw $e;
                }

                $wait = (int) min($e->seconds, $cap);
                $this->line(sprintf('flood_wait %ds on messages.getDialogs — sleeping', $wait));
                sleep($wait);
            }
        }
    }

    /**
     * The sink that STORES one `messages.getDialogs` page instead of dropping
     * it. This is the whole defect, closed.
     *
     * WHAT WAS WRONG: the page is the ONLY place Telegram hands this account
     * the `access_hash` of every peer it can reach — it rides the companion
     * `users` / `chats` vectors, and `DialogDiscovery::accessHashIndex()`
     * reads exactly those. The sink closure consumed a page's peer ENTRIES
     * and printed them, and the page's hashes were discarded with the array.
     * A live run printed
     *
     *     discovered channel:1915503741 {3:1915503741, top_message:12424}
     *     discovered user:8329480050    {1:8329480050,  top_message:103356}
     *
     * and left `tf_channels = 0, tf_users_access_hash = 0, tf_chats = 0`. The
     * run was not useless, it was INVISIBLE: `mirrorPeerLookup()` below
     * resolves a numeric `--peer` from precisely those tables, so the operator
     * read a list of crawlable ids and was then refused each of them with
     * `peer_without_access_hash` — from a run that had held the answer in
     * memory a moment earlier.
     *
     * WHY THE MIRROR RESULT ROUTE, REUSED, NOT A NEW WRITER: the catalog
     * already declares every edge this page carries
     * (src/Schema/schema/mirror-catalog.json):
     *
     *     tf_dialogs <- messages.dialogs.dialogs
     *     tf_chats   <- messages.dialogs.chats
     *     tf_users   <- messages.dialogs.users    (access_hash is a 1:1 child,
     *                                                 so it lands as
     *                                                 tf_users_access_hash)
     *
     * `MirrorNestedSourceWalker` walks those declared edges and
     * `MirrorResultIngester` writes them — the identical triple
     * `MirrorIngesterFactory::build()` uses for bus updates, and the identical
     * route `mirrorAccountProfile()` / `mirrorAccountCollections()` below use
     * for RPC results. One write path, already proven against the real catalog
     * and the real shipped DDL.
     *
     * WHY NOT `UpdateIngestor::ingest()` (the seam the history pages use):
     * that route has no surface for this payload. Its generic branch
     * (UpdateIngestor.php:322) fires on a LIST under `messages`, resolves to
     * `writeEnvelope()`, and `writeEnvelope()` iterates that list writing
     * MESSAGE rows only — it never reads `users` or `chats` at all. A page
     * carrying only the companion vectors therefore stores nothing through it,
     * which is why the vectors have to go to the mirror route instead.
     *
     * WHY THE UNGATED ROUTE: `buildResultMirror()` would apply
     * `MirrorResultPolicy` first, and this command must not depend on an
     * admission list it does not own — an operator who narrows
     * `teleframe.mirror.result_methods`, or a policy that simply does not
     * carry `messages.getDialogs`, would silently take the fix away again and
     * `--discover` would go back to printing peers and storing nothing, with no
     * error anywhere. `buildResultRoute()` is documented as the write seam for
     * "a caller that has already decided to mirror holds the decision itself",
     * and this caller HAS: it fetched this page for `$accountId` on a run the
     * operator asked for, and throwing the facts away was the bug. The decision
     * is made HERE, where the fetch happened, rather than in a list of method
     * names that governs unrelated RPC results too. It stays scoped to the
     * discovery fetch — nothing else in this command reaches it.
     *
     * WHY IT CANNOT COST AN EXTRA REQUEST: it runs on the array
     * `fetchDialogsPage()` already returned. The walk's pagination, its
     * FLOOD_WAIT retry and its page limit are untouched — `DialogDiscovery`
     * still fetches exactly the pages it fetched before, and still prints
     * exactly the lines it printed before.
     *
     * WHY A WRITE FAILURE WARNS AND CONTINUES: the same rule the profile and
     * collection fetches follow. A discovery run whose mirror write fails has
     * still learned what the account can reach, and printing that is worth
     * more than aborting a walk over a storage problem — the hashes will land
     * on the next run, and the warning says so by name.
     *
     * WHY THE CTOR IS NORMALISED FIRST: the catalog did not spell every page
     * the way the wire does, and nothing else in the stack reconciles them.
     * `messages.getDialogs` returns EITHER `messages.Dialogs` OR
     * `messages.DialogsSlice` depending on the server's answer, and the
     * decoded object's `_` is the constructor name the codec resolved:
     * MEASURED against the shipped TL registry —
     *     messages.dialogs      -> id 0x15ba6c40 -> nameOf = 'messages.dialogs'
     *     messages.dialogsSlice -> id 0x71e094f3 -> nameOf = 'messages.dialogsSlice'
     *     dialogs               -> NOT A REGISTERED CONSTRUCTOR (throws)
     * So both spellings are real and qualified, and a bare `'dialogs'` never
     * occurs on the wire. (An earlier version of this comment claimed the
     * opposite -- that the codec hands back a BARE type and that `messages.`
     * was only a scheme namespace. MEASURED FALSE, and it explained away the
     * real defect, so it is corrected here rather than left to mislead.)
     * The catalog, meanwhile, declared ONLY the `messages.dialogs` edge, and
     * `MirrorNestedSourceWalker::walk()` does one exact lookup --
     * `nestedSourcesFor((string) $node['_'])` -- with no fallback of any kind.
     * So a `dialogsSlice` page MISSED every declared edge, the walk returned
     * zero facts, and the guard rejected the page before the route was even
     * called: 103 dialogs discovered and printed, zero `stored dialogs page`
     * lines, `tf_dialogs` / `tf_users` / `tf_users_access_hash` / `tf_chats`
     * all still 0. Nothing errored, because nothing had failed -- a page
     * routed to a lookup that cannot match is indistinguishable, from the
     * output, from a page with no facts. The asymmetry is invisible from
     * either side, which is why it survived a green suite.
     *
     * @return callable(array<string, mixed>): void
     */
    private function dialogsPagePersister(int $accountId): callable
    {
        // Built ONCE per run, not per page: it parses the shipped TL scheme
        // and loads the catalog, and discovery can walk thousands of pages.
        $route = MirrorIngesterFactory::buildResultRoute();

        return function (array $page) use ($route, $accountId): void {
            $ctor = self::qualifyDialogsCtor((string) ($page['_'] ?? ''));

            // `messages.dialogsNotModified` carries no dialogs/users/chats at
            // all — it is the walk's terminal page — and any other shape has no
            // declared facts either. Handing it to the route would be a call
            // that provably writes nothing. The guard therefore reads the
            // QUALIFIED ctor, which is the only spelling that says anything
            // about facts: a shape that does not resolve to one of the two
            // dialogs ctors is skipped, whichever way it was spelled.
            if ($ctor === null) {
                return;
            }

            // ONLY `_` DIFFERS, and it differs for the walk, not for the page.
            // `$page` arrives BY VALUE, so this assignment is local to the
            // closure: the page `DialogDiscovery` reads its peers and its
            // pagination from keeps whatever constructor came off the wire —
            // QUALIFIED, which is the spelling the wire really produces, so
            // the bare entries in the map are defensive, not what arrives.
            $page['_'] = $ctor;

            try {
                $written = $route($page, $accountId);
            } catch (Throwable $e) {
                $this->warn(sprintf(
                    'mirror: messages.getDialogs page NOT stored for account=%d (%s) — --peer=<id> stays '
                    .'unresolvable until a later run stores it; discovery continues',
                    $accountId,
                    $e->getMessage(),
                ));

                return;
            }

            // `facts:` was the worst of the three labels: it named what the page
            // CARRIED rather than what the writer placed, so it read as a count of
            // stored facts. Now `statements:` — same number, honest name. See the
            // label correction above.
            if ($written['inserted'] > 0) {
                $this->line(sprintf(
                    'stored dialogs page account=%d {statements:%d}',
                    $accountId,
                    $written['inserted'],
                ));
            }

            foreach ($written['fkClues'] as $clue) {
                $this->components->warn("mirror FK clue: {$clue}");
            }
            foreach ($written['unmappedClues'] as $clue) {
                $this->components->warn("mirror unmapped field: {$clue}");
            }
            // Own label, never folded into the FK list: a value fault is the
            // VALUE not fitting the column the DDL gives it (MySQL
            // 1265/1364/1366/1406, Postgres 22001) — a column-shape problem,
            // not a relationship one. Empty list = silent, as above.
            foreach ($written['valueClues'] as $clue) {
                $this->components->warn("mirror value clue: {$clue}");
            }

            $this->recordClues($accountId, $written);
        };
    }

    /**
     * The dialogs page's `_` mapped to the spelling the catalog declares its
     * edges under, or null when the page declares no facts.
     *
     * WHY A MAP AND NOT A SUFFIX RULE: the bare form is not derivable from the
     * qualified one by stripping, because `dialogsSlice` is not `dialogs` plus
     * anything — it is a second ctor that happens to share a prefix. And a
     * general "add `messages.` to any unprefixed ctor" rule would sweep in
     * ctors this command never fetches, converting the caller's skip into a
     * route call that provably writes nothing. Returning null for those is the
     * honest answer, and it is what `messages.dialogsNotModified` — a real
     * scheme name, a terminal page, and a page with no `users` / `chats` on it
     * — gets.
     */
    private static function qualifyDialogsCtor(string $ctor): ?string
    {
        return self::DIALOGS_CTOR_BY_WIRE_TYPE[$ctor] ?? null;
    }

    /**
     * @return callable(int): UserAccountScope
     */
    private function resolveScope(): callable
    {
        $resolver = $this->getLaravel()->make(self::SCOPE_RESOLVER_KEY);

        if (! is_callable($resolver) || is_string($resolver)) {
            throw new RuntimeException(self::SCOPE_RESOLVER_KEY.' must bind a callable(int): UserAccountScope');
        }

        return $resolver(...);
    }

    /**
     * @return callable(int): (callable(list<array<string, mixed>>): array<string, mixed>)
     */
    private function resolveIngester(): callable
    {
        $factory = $this->getLaravel()->make(self::INGESTER_KEY);

        if (! is_callable($factory) || is_string($factory)) {
            throw new RuntimeException(self::INGESTER_KEY.' must bind a callable(int): callable');
        }

        return $factory(...);
    }

    /**
     * Resolve the durable cursor ledger (Cycle 17).
     *
     * --no-persist is the ONLY way to get a non-durable store, and it says so
     * out loud. An unbound seam throws rather than quietly handing back a
     * MemoryCursorStore, because "no ledger" is indistinguishable from "a fresh
     * crawl" in the output — which is precisely the silent data-loss shape that
     * made the full history unreachable in the first place.
     */
    private function resolveCursorStore(int $accountId): CursorStoreContract
    {
        if ($this->option('no-persist')) {
            $this->warn('--no-persist: cursors stay in memory, so this run\'s progress is LOST on exit.');

            return new MemoryCursorStore;
        }

        if (! $this->getLaravel()->bound(self::CURSOR_STORE_KEY)) {
            throw new RuntimeException(sprintf(
                '%s is not bound. The backfill cursor ledger is what lets a throttled crawl RESUME '
                .'and reach message id 1 (verbatim 2026-09-16: "i need the full acount hsitory in my '
                .'database"). Bind it to a callable(int): %s, or pass --no-persist to opt out '
                .'explicitly and accept that progress is lost on exit.',
                self::CURSOR_STORE_KEY,
                CursorStoreContract::class,
            ));
        }

        $factory = $this->getLaravel()->make(self::CURSOR_STORE_KEY);

        if (! is_callable($factory) || is_string($factory)) {
            throw new RuntimeException(
                self::CURSOR_STORE_KEY.' must bind a callable(int): '.CursorStoreContract::class,
            );
        }

        $store = $factory($accountId);

        if (! $store instanceof CursorStoreContract) {
            throw new RuntimeException(sprintf(
                '%s factory must return a %s, got %s',
                self::CURSOR_STORE_KEY,
                CursorStoreContract::class,
                get_debug_type($store),
            ));
        }

        return $store;
    }

    private function configInt(string $key, int $fallback): int
    {
        $value = $this->getLaravel()->make('config')->get('teleframe.backfill.'.$key);

        return is_numeric($value) ? (int) $value : $fallback;
    }
}
