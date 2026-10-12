<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Control\RelaySessionRegistry;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\Concerns\ConcernsScopesVault;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayActiveAccount;
use MeRezaRezaei\Teleframe\Mirror\Models\TfChannel;
use MeRezaRezaei\Teleframe\Mirror\Models\TfChat;
use MeRezaRezaei\Teleframe\Mirror\Models\TfDialog;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUsersFirstName;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUsersLastName;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUsersUsername;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;

/**
 * The `/tg` browser view: what the relay-managed vault accounts hold, seen
 * from a browser, with account switching in the middle.
 *
 * WHY A SERVER-RENDERED VIEW AND NOT A CLIENT BUILD. The operator's words
 * for this ("a telegram web client ... in the middle we can control what
 * data is passing to the client") describe a relay seam, not a bundler:
 * `teleframe:relay` already sits in the middle for a patched web client.
 * This route is the OTHER half — a host-authenticated read model of the
 * same vault, renderable with no npm, no vite and no dist/ directory, so
 * the account list is provable in CI and inspectable in `route:list`. It
 * deliberately renders NO login form: the accounts here were already
 * linked through the dashboard's phone/2FA flow, so a stock QR/phone
 * screen on this page would be a second, redundant way to obtain a
 * credential the operator already owns.
 *
 * WHAT IS RENDERED, AND FROM WHERE. Accounts come from the vault through
 * `AccountsController::payload()` — the SAME projection the JSON
 * `GET accounts` endpoint serves, reused rather than re-shaped, so the
 * view and the API can never disagree. Per-account relay presence comes
 * from `RelaySessionRegistry` (a live session is one a relay is holding,
 * exactly what `GET accounts/{id}/presence` reports). Which account is
 * ACTIVE comes from `RelayActiveAccount::currentFor()` under the SAME
 * tenant scope `RelaySwitchController` writes, which is what makes the
 * switch button on this page agree with the relay.
 *
 * The chat list is the MIRROR, never a live RPC: `tf_dialogs` rows for the
 * active account, with the peer title resolved from `tf_users` /
 * `tf_chats` / `tf_channels` and the preview from the dialog's
 * `top_message` in `tf_messages`. That is data this package has already
 * ingested — reading it proves nothing was faked and dials nothing. When
 * the mirror has no tables (a host that has not run the schema
 * migrations), or no row for the active account, the page says so in
 * plain words instead of rendering an empty list that looks like "this
 * account has no chats". A page that cannot distinguish those two states
 * is worse than no page.
 *
 * CREDENTIALS. Nothing on this page is a credential. The session string,
 * the auth key and the app's api_hash never leave the vault: the account
 * payload carries `has_session` as a BOOLEAN, and presence is a session
 * liveness boolean. There is deliberately no code path here that could
 * render one even if asked — nothing in the view data is derived from
 * `$account->session`.
 *
 * Tenant scoping is `ConcernsScopesVault`, the same trait the ticket,
 * presence and switch seams use, so a request can only ever see the
 * authenticated owner's accounts; an unauthenticated one resolves to zero
 * rows (and is refused by the host's `auth` guard before it gets here).
 *
 * Plain-PHP constructible: no constructor arguments, no container calls in
 * the constructor (the standalone-smoke gate constructs it bare).
 */
final class TgController extends Controller
{
    use ConcernsScopesVault;

    /**
     * The mirror tables this view reads. Checked per request rather than
     * assumed: a host that never ran the schema migrations must get a
     * rendered "not mirrored" state, not a 500 from a missing relation.
     */
    private const MIRROR_TABLES = [
        'tf_dialogs',
        'tf_messages',
        'tf_users',
        'tf_users_first_name',
        'tf_users_last_name',
        'tf_users_username',
        'tf_chats',
        'tf_channels',
    ];

    /** Chat-list page size. A dialog list is a screen, not a dump. */
    private const MAX_DIALOGS = 50;

    /** Preview text is clipped for the same reason a real client clips it. */
    private const MAX_PREVIEW_CHARS = 160;

    /** Peer shape discriminators, per the mirror's peer FK targets. */
    private const PEER_USER = 1;

    private const PEER_CHAT = 2;

    private const PEER_CHANNEL = 3;

    /**
     * Render the view.
     *
     * `RelayActiveAccount` and `RelaySessionRegistry` are method
     * dependencies, so they are resolved per request; neither dials, reads
     * the vault, or builds a client — `currentFor()` and `status()` are
     * cache reads.
     */
    public function show(Request $request, RelayActiveAccount $active, RelaySessionRegistry $registry): View
    {
        $accounts = $this->scopedAccounts($request)
            ->with('app')
            ->latest('id')
            ->get();

        $payloads = $accounts
            ->map(function (TelegramAccount $account) use ($registry): array {
                $payload = AccountsController::payload($account);

                $payload['online'] = $registry->status((int) $account->getKey()) !== null;
                $payload['switch_url'] = route('teleframe.accounts.switch', ['id' => (int) $account->getKey()]);
                $payload['presence_url'] = route('teleframe.accounts.presence', ['id' => (int) $account->getKey()]);

                return $payload;
            })
            ->values()
            ->all();

        $context = $active->currentFor($this->scopeId($request));
        $activeId = $context?->accountId;

        // The chat list belongs to the ACTIVE account only. Falling back to
        // "the first account" would render one account's chats under
        // another account's name, which is the one confusion this page must
        // never produce.
        $chats = $activeId === null ? [] : $this->chats($activeId);
        $mirrored = $this->mirrorReady();

        return view('teleframe::tg', [
            'accounts' => $payloads,
            'activeAccountId' => $activeId,
            'activeLabel' => $context?->label,
            'activeOnline' => $activeId === null ? false : $registry->status($activeId) !== null,
            'switchGeneration' => $active->currentGenerationFor($this->scopeId($request)),
            'chats' => $chats,
            'mirrorReady' => $mirrored,
            'liveEndpoint' => $this->liveEndpoint(),
            'userLabel' => $this->userLabel($request),
        ]);
    }

    /**
     * The active account's dialogs with a resolved title and preview.
     *
     * Ordering is by the dialog's own `top_message`, which is what the
     * mirror recorded as the newest message in that chat — a real
     * recency order, not a guess from an insertion order that survives
     * backfills. Every read is account-scoped through `forAccount()`,
     * which drops the ambient `AccountScope` (an HTTP request may carry a
     * host-wide `teleframe.primary_account_id`) and re-filters explicitly,
     * so one account's chats can never surface under another's.
     *
     * @return list<array{peer_type: int, peer_id: int, title: string, preview: ?string, date: ?string, out: bool, unread: int, pinned: bool}>
     */
    private function chats(int $accountId): array
    {
        if (! $this->mirrorReady()) {
            return [];
        }

        $dialogs = TfDialog::forAccount($accountId)
            ->orderByDesc('top_message')
            ->limit(self::MAX_DIALOGS)
            ->get();

        if ($dialogs->isEmpty()) {
            return [];
        }

        // A message's identity is (peer_type, peer_id, id) — see
        // TfMessage::MESSAGE_KEY. `id` alone is NOT unique: Telegram numbers
        // messages PER CHAT, and 136 dialogs of one real account held a message
        // numbered 1. So the preview lookup carries the whole composite, and a
        // flat id list would let two dialogs render each other's text.
        $topKeys = [];
        foreach ($dialogs as $dialog) {
            $topMessage = (int) $dialog->top_message;
            if ($topMessage > 0) {
                $topKeys[] = [(int) $dialog->peer_type, (int) $dialog->peer_id, $topMessage];
            }
        }

        $previews = $this->previews($accountId, $topKeys);
        $titles = $this->titles($accountId, $dialogs);

        $chats = [];
        foreach ($dialogs as $dialog) {
            $peerType = (int) $dialog->peer_type;
            $peerId = (int) $dialog->peer_id;
            $key = $peerType.':'.$peerId;
            $preview = $previews[self::messageKey($peerType, $peerId, (int) $dialog->top_message)] ?? null;

            $chats[] = [
                'peer_type' => $peerType,
                'peer_id' => $peerId,
                // An unresolved peer is labelled by its TYPE, never by a
                // guessed name: "chat 12345" is honest, "Chat" is a lie.
                'title' => $titles[$key] ?? $this->fallbackTitle($peerType, $peerId),
                'preview' => $preview === null ? null : $this->clip($preview['message']),
                'date' => $preview === null ? null : $this->stamp($preview['date']),
                'out' => $preview === null ? false : $preview['out'],
                'unread' => max(0, (int) $dialog->unread_count),
                'pinned' => (bool) $dialog->pinned,
            ];
        }

        return $chats;
    }

    /**
     * The newest message per dialog, keyed by the FULL message key
     * `peerType:peerId:id` — the dialog's own `top_message` scoped to its peer.
     * One query for the whole page rather than one per dialog: a 50-row page
     * would otherwise be 50 round trips.
     *
     * @param  list<array{int, int, int}>  $keys  [peer_type, peer_id, message id]
     * @return array<string, array{message: string, out: bool, date: int}>
     */
    private function previews(int $accountId, array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        // A row-value IN over the table's own primary key
        // `(account_id, peer_type, peer_id, id)`. `account_id` is supplied by the
        // AccountScoped global scope, so every tuple is a PK seek and not a
        // scan, and the composite the index already carries is exactly what is
        // being asked for. No migration is needed and none should be added.
        //
        // WHY A PARAMETERISED OR-CHAIN AND NOT `whereIn`. Laravel's `whereIn`
        // rejects a nested array outright ("Nested arrays may not be passed to
        // whereIn method") and `whereRowValues` accepts only a SINGLE tuple, so
        // neither expresses `(a,b,c) IN ((..),(..))`. The alternatives were
        // both worse: one `whereIn` per column fetches the whole CROSS PRODUCT
        // of peer_type x peer_id x id, which on a real account returns far more
        // rows than the page asked for; and OR-ing the tuples through a
        // `where(Closure)` narrows the builder away from `TfMessage`, after
        // which every property read on a returned row is an undefined-property
        // error under phpstan level 5.
        //
        // The clause is built from BOUNDED integers — three per dialog, at most
        // MAX_DIALOGS dialogs — and every value travels as a `?` binding, so
        // there is nothing interpolated into the SQL text.
        $clauses = [];
        $bindings = [];
        foreach ($keys as [$peerType, $peerId, $messageId]) {
            $clauses[] = '(peer_type = ? AND peer_id = ? AND id = ?)';
            $bindings[] = $peerType;
            $bindings[] = $peerId;
            $bindings[] = $messageId;
        }

        // keyBy the COMPOSITE, not `id`: keying by the bare id would let the
        // LAST dialog to write a given id overwrite every other dialog's row
        // for that id, and both would then read back the same text.
        return TfMessage::forAccount($accountId)
            ->whereRaw('('.implode(' OR ', $clauses).')', $bindings)
            ->get()
            ->keyBy(static fn (object $row): string => self::messageKey(
                (int) $row->peer_type,
                (int) $row->peer_id,
                (int) $row->id
            ))
            ->map(static fn (object $row): array => [
                'message' => (string) $row->message,
                'out' => (bool) $row->out,
                'date' => (int) $row->date,
            ])
            ->all();
    }

    /**
     * The message identity the mirror uses everywhere else: peer first, then
     * the per-dialog id. `TfMessage::MESSAGE_KEY` is the authority; this is the
     * string form of it, for use as an in-memory map key.
     */
    private static function messageKey(int $peerType, int $peerId, int $messageId): string
    {
        return $peerType.':'.$peerId.':'.$messageId;
    }

    /**
     * Peer display names for the page's dialogs, keyed `peerType:peerId`.
     *
     * A user is first + last name (or username when there is no name); a
     * chat and a channel carry an inline `title`. Names live in 1:1 child
     * tables where they exist at all — row existence IS fact existence in
     * this mirror — so an absent row yields no name, never an empty one
     * presented as a real name.
     *
     * @param  Collection<int, object>  $dialogs
     * @return array<string, string>
     */
    private function titles(int $accountId, Collection $dialogs): array
    {
        $userIds = [];
        $chatIds = [];
        $channelIds = [];

        foreach ($dialogs as $dialog) {
            match ((int) $dialog->peer_type) {
                self::PEER_USER => $userIds[(int) $dialog->peer_id] = true,
                self::PEER_CHAT => $chatIds[(int) $dialog->peer_id] = true,
                self::PEER_CHANNEL => $channelIds[(int) $dialog->peer_id] = true,
                default => null,
            };
        }

        $titles = [];

        foreach ($this->userNames($accountId, array_keys($userIds)) as $id => $name) {
            $titles[self::PEER_USER.':'.$id] = $name;
        }

        foreach ($this->inlineTitles(TfChat::class, $accountId, array_keys($chatIds)) as $id => $name) {
            $titles[self::PEER_CHAT.':'.$id] = $name;
        }

        foreach ($this->inlineTitles(TfChannel::class, $accountId, array_keys($channelIds)) as $id => $name) {
            $titles[self::PEER_CHANNEL.':'.$id] = $name;
        }

        return $titles;
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    private function userNames(int $accountId, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $first = $this->nameColumn(TfUsersFirstName::class, $accountId, $ids, 'first_name');
        $last = $this->nameColumn(TfUsersLastName::class, $accountId, $ids, 'last_name');
        $username = $this->nameColumn(TfUsersUsername::class, $accountId, $ids, 'username');

        $names = [];
        foreach ($ids as $id) {
            $name = trim(($first[$id] ?? '').' '.($last[$id] ?? ''));
            if ($name === '') {
                $handle = $username[$id] ?? '';
                if ($handle !== '') {
                    $names[$id] = '@'.$handle;
                }
            } else {
                $names[$id] = $name;
            }
        }

        return $names;
    }

    /**
     * One name column of a 1:1 fact table, keyed by the parent id.
     *
     * @param  class-string<TfUsersFirstName|TfUsersLastName|TfUsersUsername>  $model
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    private function nameColumn(string $model, int $accountId, array $ids, string $column): array
    {
        return $model::forAccount($accountId)
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(static fn (object $row): array => [(int) $row->id => (string) $row->{$column}])
            ->all();
    }

    /**
     * Inline `title` for the chat/channel parent tables.
     *
     * @param  class-string<TfChat|TfChannel>  $model
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    private function inlineTitles(string $model, int $accountId, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $model::forAccount($accountId)
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(static fn (object $row): array => [(int) $row->id => (string) $row->title])
            ->filter(static fn (string $title): bool => $title !== '')
            ->all();
    }

    private function fallbackTitle(int $peerType, int $peerId): string
    {
        return match ($peerType) {
            self::PEER_USER => 'user '.$peerId,
            self::PEER_CHAT => 'chat '.$peerId,
            self::PEER_CHANNEL => 'channel '.$peerId,
            default => 'peer '.$peerId,
        };
    }

    private function clip(string $text): ?string
    {
        $text = trim(str_replace(["\r", "\n", "\t"], ' ', $text));
        if ($text === '') {
            return null;
        }

        if (strlen($text) <= self::MAX_PREVIEW_CHARS) {
            return $text;
        }

        return substr($text, 0, self::MAX_PREVIEW_CHARS - 1).'…';
    }

    private function stamp(mixed $date): ?string
    {
        if (! is_int($date) || $date <= 0) {
            return null;
        }

        return Carbon::createFromTimestamp($date)->toIso8601String();
    }

    /**
     * Whether the mirror tables this page reads exist at all. A `hasTable`
     * per table rather than a blanket try/catch around the query, so an
     * unrelated database failure still surfaces as itself instead of being
     * reported as "the mirror is not installed".
     */
    private function mirrorReady(): bool
    {
        foreach (self::MIRROR_TABLES as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function liveEndpoint(): ?string
    {
        $endpoint = config('teleframe.telegram_web.live_endpoint');

        return is_string($endpoint) && $endpoint !== '' ? $endpoint : null;
    }

    private function userLabel(Request $request): string
    {
        $user = $request->user();

        return $user === null ? '' : (string) ($user->getAttribute('email') ?? $user->getKey());
    }

    /**
     * The tenant namespace the ACTIVE context is stored under — the same
     * `owner_type:owner_id` scope `RelaySwitchController` writes, so the
     * account this page marks active is the account the relay renders
     * active (W-09).
     */
    private function scopeId(Request $request): string
    {
        $user = $request->user();

        return $user !== null ? $user::class.'#'.$user->getKey() : 'anonymous';
    }
}
