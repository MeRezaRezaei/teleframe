{{-- The `/tg` view: what the relay-managed vault accounts hold, and the account switch
     in the middle. Server-rendered Blade on the dashboard shell's styling vocabulary
     (CSS custom properties, section/table/row), zero-build: no npm, no vite, no dist/.

     The chat list comes from the MIRROR (tf_dialogs / tf_users / tf_chats / tf_channels /
     tf_messages) for whichever account is ACTIVE — never from a live RPC, and never from
     invented data. When the mirror has nothing for that account this page says so; it
     never renders a phone/QR login form, because these accounts were already linked
     through the dashboard and a second credential path would be a way to re-obtain a
     session the host already owns. No session string, auth key or api_hash is ever in
     the data this view receives — presence is a liveness boolean. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teleframe · Telegram</title>
    <style>
        :root { --bg:#0f172a; --panel:#1e293b; --line:#334155; --text:#e2e8f0; --muted:#94a3b8; --accent:#38bdf8; --ok:#4ade80; --err:#f87171; }
        * { box-sizing:border-box; }
        body { margin:0; font:15px/1.5 system-ui, sans-serif; background:var(--bg); color:var(--text); }
        header { padding:1rem 1.5rem; border-bottom:1px solid var(--line); display:flex; gap:1rem; align-items:center; }
        header h1 { font-size:1.1rem; margin:0; }
        header .pill { margin-left:auto; font-size:.8rem; color:var(--muted); }
        main { max-width:960px; margin:0 auto; padding:1.5rem; }
        section { background:var(--panel); border:1px solid var(--line); border-radius:8px; padding:1.25rem; margin-bottom:1.5rem; }
        h2 { font-size:1rem; margin:0 0 .75rem; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; }
        table { width:100%; border-collapse:collapse; }
        th, td { text-align:left; padding:.5rem .25rem; border-bottom:1px solid var(--line); font-size:.9rem; }
        th { color:var(--muted); font-weight:500; }
        tr:last-child td { border-bottom:none; }
        button { padding:.45rem .9rem; border:none; border-radius:6px; background:var(--accent); color:#082032; font-weight:600; cursor:pointer; }
        button.secondary { background:transparent; border:1px solid var(--line); color:var(--text); }
        button:disabled { opacity:.5; cursor:not-allowed; }
        .msg { margin-top:.75rem; font-size:.85rem; min-height:1.2em; }
        .msg.ok { color:var(--ok); } .msg.err { color:var(--err); }
        .muted { color:var(--muted); font-size:.85rem; }
        .badge { display:inline-block; padding:.1rem .5rem; border-radius:99px; background:#0b1220; border:1px solid var(--line); font-size:.75rem; color:var(--muted); }
        .badge.on { color:var(--ok); } .badge.off { color:var(--err); }
        .unread { color:var(--accent); font-weight:600; font-size:.8rem; }
        .you { color:var(--muted); }
        .chat-preview { color:var(--muted); }
    </style>
</head>
<body>
    <header>
        <h1>Teleframe · Telegram</h1>
        <span class="pill">{{ $userLabel }}</span>
    </header>

    <main>
        {{-- Accounts + the switch. POST accounts/{id}/switch is the endpoint the
             RelaySwitchController already exposes; this page only calls it. --}}
        <section>
            <h2>Accounts</h2>
            <table>
                <thead>
                <tr>
                    <th>Label</th>
                    <th>Type</th>
                    <th>Telegram ID</th>
                    <th>DC</th>
                    <th>Session</th>
                    <th>Relay</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($accounts as $account)
                    <tr @if($account['id'] === $activeAccountId) style="color:var(--ok)" @endif>
                        <td>{{ $account['label'] }}</td>
                        <td>{{ $account['type'] }}</td>
                        <td>{{ $account['user_id'] ?? '-' }}</td>
                        <td>{{ $account['dc_id'] }}</td>
                        {{-- `has_session` is the vault's BOOLEAN, not the session itself. --}}
                        <td>{{ $account['has_session'] ? 'linked' : '-' }}</td>
                        <td>
                            <span class="badge {{ $account['online'] ? 'on' : 'off' }}">{{ $account['online'] ? 'connected' : 'not connected' }}</span>
                        </td>
                        <td>
                            @if($account['id'] === $activeAccountId)
                                <span class="badge on">active</span>
                            @else
                                <button data-switch-url="{{ $account['switch_url'] }}"
                                        data-account-id="{{ $account['id'] }}"
                                        {{ $account['has_session'] ? '' : 'disabled' }}
                                        title="{{ $account['has_session'] ? 'Render this account active for the relay' : 'No session to render active' }}">
                                    Switch
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="muted">
                            No linked accounts. Link one from the <a href="{{ url(config('teleframe.dashboard.prefix', 'teleframe')) }}" style="color:var(--accent)">dashboard</a>.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            <div class="msg" id="switch-msg"></div>
        </section>

        {{-- Active context: whose chats the list below belongs to, and whether a relay
             is holding the session. Both come from the package's own state, so this
             header is what makes the list attributable to one account. --}}
        <section>
            <h2>Active account</h2>
            @if($activeAccountId === null)
                <p class="muted" style="margin:0">
                    No account is active yet — switch one above and its chats appear here.
                </p>
            @else
                <p style="margin:0">
                    <strong>{{ $activeLabel }}</strong> (account {{ $activeAccountId }})
                    <span class="badge {{ $activeOnline ? 'on' : 'off' }}">{{ $activeOnline ? 'connected' : 'not connected' }}</span>
                    <span class="muted">switch generation {{ $switchGeneration }}</span>
                    @if($liveEndpoint !== null)
                        <span class="muted">· relay endpoint {{ $liveEndpoint }}</span>
                    @endif
                </p>
            @endif
        </section>

        {{-- The chat list: the mirror's dialogs for the active account. An empty table
             here would be indistinguishable from "this account has no chats", so the
             three empty cases are stated separately. --}}
        <section>
            <h2>Chats</h2>
            @if($activeAccountId === null)
                <p class="muted" style="margin:0">Switch an account above to see its chats.</p>
            @elseif(! $mirrorReady)
                <p class="muted" style="margin:0">
                    Not mirrored: this install has no Telegram mirror tables, so there is
                    nothing to list. Run the package migrations and re-run the ingest /
                    backfill to populate the chat list.
                </p>
            @elseif($chats === [])
                <p class="muted" style="margin:0">
                    No mirrored chats for this account yet. The relay is
                    @if($activeOnline) connected but nothing has been ingested @else not connected, so nothing has been ingested @endif
                    — start the daemon or a backfill to fill the list.
                </p>
            @else
                <table>
                    <thead>
                    <tr>
                        <th>Chat</th>
                        <th>Last message</th>
                        <th>When</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($chats as $chat)
                        <tr>
                            <td>
                                {{ $chat['title'] }}
                                @if($chat['pinned'])
                                    <span class="badge">pinned</span>
                                @endif
                            </td>
                            <td class="chat-preview">
                                @if($chat['preview'] === null)
                                    <span class="muted">no message text</span>
                                @else
                                    <span class="you">@if($chat['out'])You: @endif</span>{{ $chat['preview'] }}
                                @endif
                            </td>
                            <td class="muted">{{ $chat['date'] ?? '-' }}</td>
                            <td>
                                @if($chat['unread'] > 0)
                                    <span class="unread">{{ $chat['unread'] }} unread</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </main>

    <script>
        (function () {
            const msg = (el, text, ok) => { el.textContent = text; el.className = 'msg ' + (ok ? 'ok' : 'err'); };

            async function switchTo(url) {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: '{}',
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) { throw new Error(data.message || ('HTTP ' + res.status)); }
                return data;
            }

            document.querySelectorAll('button[data-switch-url]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const out = document.getElementById('switch-msg');
                    button.disabled = true;
                    try {
                        await switchTo(button.dataset.switchUrl);
                        msg(out, 'Account ' + button.dataset.accountId + ' is now active — reloading its chats.', true);
                        window.location.reload();
                    } catch (e) {
                        msg(out, e.message, false);
                        button.disabled = false;
                    }
                });
            });
        })();
    </script>
</body>
</html>
