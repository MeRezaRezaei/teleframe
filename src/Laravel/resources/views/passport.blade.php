{{-- Telegram Passport login page (the HTTP surface of the passport engine). --}}
{{-- Same styling vocabulary as the dashboard shell (CSS custom properties, section/table/row, --}}
{{-- zero-build, no framework). POSTs to the package's own start endpoint and posts the --}}
{{-- credentials a host received from Telegram back to its callback — no glue code required. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teleframe · Telegram Passport</title>
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
        label { display:block; font-size:.8rem; color:var(--muted); margin:.5rem 0 .2rem; }
        input, select, textarea { width:100%; padding:.45rem .6rem; border:1px solid var(--line); border-radius:6px; background:#0b1220; color:var(--text); font-family:inherit; }
        button { padding:.45rem .9rem; border:none; border-radius:6px; background:var(--accent); color:#082032; font-weight:600; cursor:pointer; }
        button.secondary { background:transparent; border:1px solid var(--line); color:var(--text); }
        button:disabled { opacity:.5; cursor:not-allowed; }
        .row { display:flex; gap:.75rem; align-items:flex-end; }
        .row > div { flex:1; }
        .msg { margin-top:.75rem; font-size:.85rem; min-height:1.2em; }
        .msg.ok { color:var(--ok); } .msg.err { color:var(--err); }
        .muted { color:var(--muted); font-size:.85rem; }
        .badge { display:inline-block; padding:.1rem .5rem; border-radius:99px; background:#0b1220; border:1px solid var(--line); font-size:.75rem; color:var(--muted); }
        .badge.on { color:var(--ok); } .badge.off { color:var(--err); }
        code { background:#0b1220; border:1px solid var(--line); border-radius:4px; padding:.1rem .3rem; font-size:.8rem; word-break:break-all; }
    </style>
</head>
<body>
    <header>
        <h1>Teleframe · Telegram Passport</h1>
        <span class="pill">{{ $userLabel }}</span>
    </header>

    <main>
        {{-- Status: the module is inert unless explicitly configured. --}}
        <section>
            <h2>Status</h2>
            <p style="margin:0">
                Passport login is
                <span class="badge {{ $enabled ? 'on' : 'off' }}">{{ $enabled ? 'enabled' : 'disabled' }}</span>
            </p>
            @if(! $enabled)
                <p class="muted" style="margin:.75rem 0 0">
                    Set <code>teleframe.passport.enabled</code> to true (plus a bot RSA key path and a nonce store)
                    before starting a login. Nothing is issued while it is off.
                </p>
            @endif
        </section>

        {{-- Start: the package issues the one-time nonce and hands back the Telegram handshake. --}}
        <section>
            <h2>Start a login</h2>
            <p class="muted" style="margin:0 0 .75rem">
                Issues a one-time nonce bound to this session and returns the
                <code>requestPassport</code> arguments for your bot, a
                <code>t.me</code> deep link and the callback URL.
            </p>
            <div class="row">
                <button id="passport-start">Issue nonce</button>
            </div>
            <div id="passport-handoff" style="margin-top:.75rem"></div>
            <div class="msg" id="passport-start-msg"></div>
        </section>

        {{-- Callback: credentials Telegram returned, posted to the package's own endpoint. --}}
        <section>
            <h2>Complete a login</h2>
            <p class="muted" style="margin:0 0 .75rem">
                The credentials your bot (or the Telegram Passport widget) received:
                <code>data</code>, <code>hash</code>, <code>secret</code>, plus the sender's
                Telegram user id. The package decrypts, verifies the nonce, resolves the
                identity and logs the user in — the fields are never re-read here.
            </p>
            <label for="passport-data">data</label>
            <textarea id="passport-data" rows="3"></textarea>
            <label for="passport-hash">hash</label>
            <input id="passport-hash">
            <label for="passport-secret">secret</label>
            <input id="passport-secret">
            <label for="passport-from-id">Telegram user id (message.from.id)</label>
            <input id="passport-from-id">
            <div class="row" style="margin-top:.9rem">
                <div><label for="passport-password">2FA password (only if required)</label><input id="passport-password" type="password"></div>
                <button id="passport-callback-submit">Log in</button>
            </div>
            <div class="msg" id="passport-callback-msg"></div>
        </section>
    </main>

    <script>
        (function () {
            const startUrl = @json($startUrl);
            const callbackUrl = @json($callbackUrl);

            const msg = (el, text, ok) => { el.textContent = text; el.className = 'msg ' + (ok ? 'ok' : 'err'); };

            function esc(s) { return String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }

            async function api(path, method, body) {
                const res = await fetch(path, {
                    method: method || 'GET',
                    headers: body ? { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } : { 'X-Requested-With': 'XMLHttpRequest' },
                    body: body ? JSON.stringify(body) : undefined,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) { throw new Error(data.message || ('HTTP ' + res.status)); }
                return data;
            }

            function renderHandoff(data) {
                const box = document.getElementById('passport-handoff');
                box.innerHTML = '';

                if (data.telegram_url) {
                    const a = document.createElement('a');
                    a.href = data.telegram_url;
                    a.textContent = 'Open Telegram to share your Passport data →';
                    a.style.color = 'var(--accent)';
                    const p = document.createElement('p');
                    p.style.margin = '.6rem 0';
                    p.appendChild(a);
                    box.appendChild(p);
                }

                const table = document.createElement('table');
                const rows = [
                    ['nonce', data.nonce],
                    ['expires in', (data.expires_in || 0) + 's'],
                    ['bot', (data.bot_username ? '@' + data.bot_username : '(unset)') + ' (id ' + (data.bot_id || 0) + ')'],
                    ['scope', (data.scope || []).join(', ')],
                    ['bot command', data.bot_command],
                    ['callback', callbackUrl],
                ];
                table.innerHTML = '<thead><tr><th>Field</th><th>Value</th></tr></thead>';
                const body = document.createElement('tbody');
                rows.forEach(([k, v]) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + esc(k) + '</td><td><code>' + esc(v) + '</code></td>';
                    body.appendChild(tr);
                });
                table.appendChild(body);
                box.appendChild(table);

                // Fill the callback form's nonce holder: the decrypted payload
                // must return exactly this nonce, so keep it out of the DOM
                // form the operator pastes into.
                box.dataset.nonce = data.nonce;
            }

            document.getElementById('passport-start').addEventListener('click', async () => {
                const out = document.getElementById('passport-start-msg');
                try {
                    const data = await api(startUrl, 'POST', {});
                    renderHandoff(data);
                    msg(out, 'Nonce issued for this session.', true);
                } catch (e) { msg(out, e.message, false); }
            });

            document.getElementById('passport-callback-submit').addEventListener('click', async () => {
                const out = document.getElementById('passport-callback-msg');
                const value = (id) => document.getElementById(id).value.trim();
                if (!value('passport-data') || !value('passport-hash') || !value('passport-secret') || !value('passport-from-id')) {
                    msg(out, 'data, hash, secret and the Telegram user id are all required.', false);
                    return;
                }
                const body = {
                    data: value('passport-data'),
                    hash: value('passport-hash'),
                    secret: value('passport-secret'),
                    from_id: value('passport-from-id'),
                };
                if (value('passport-password')) { body.password = value('passport-password'); }
                try {
                    const data = await api(callbackUrl, 'POST', body);
                    msg(out, 'Logged in as Telegram user ' + (data.tg_user_id || '?') + '.', true);
                } catch (e) { msg(out, e.message, false); }
            });
        })();
    </script>
</body>
</html>
