{{-- Teleframe dashboard shell (Horizon-style, zero-build). --}}
{{-- The JSON API endpoints it calls live under the same prefix, protected by the host's
     auth guard configured in teleframe.dashboard.middleware. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teleframe</title>
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
        input, select { width:100%; padding:.45rem .6rem; border:1px solid var(--line); border-radius:6px; background:#0b1220; color:var(--text); }
        button { padding:.45rem .9rem; border:none; border-radius:6px; background:var(--accent); color:#082032; font-weight:600; cursor:pointer; }
        button.secondary { background:transparent; border:1px solid var(--line); color:var(--text); }
        button.danger { background:transparent; border:1px solid var(--err); color:var(--err); }
        button:disabled { opacity:.5; cursor:not-allowed; }
        .row { display:flex; gap:.75rem; align-items:flex-end; }
        .row > div { flex:1; }
        .msg { margin-top:.75rem; font-size:.85rem; min-height:1.2em; }
        .msg.ok { color:var(--ok); } .msg.err { color:var(--err); }
        .step { display:none; } .step.active { display:block; }
        .muted { color:var(--muted); font-size:.85rem; }
        .badge { display:inline-block; padding:.1rem .5rem; border-radius:99px; background:#0b1220; border:1px solid var(--line); font-size:.75rem; color:var(--muted); }
    </style>
</head>
<body>
    <header>
        <h1>Teleframe</h1>
        <span class="pill" id="user-pill">dashboard</span>
    </header>

    <main>
        {{-- App form --}}
        <section>
            <h2>Add Telegram app</h2>
            <div class="row">
                <div><label for="app-label">Label</label><input id="app-label" placeholder="my-app"></div>
                <div><label for="app-api-id">API ID</label><input id="app-api-id" type="number" placeholder="1234567"></div>
                <div><label for="app-api-hash">API hash</label><input id="app-api-hash" placeholder="0123456789abcdef…"></div>
                <button id="app-submit">Save</button>
            </div>
            <div class="msg" id="app-msg"></div>
        </section>

        {{-- Apps list --}}
        <section>
            <h2>Apps</h2>
            <table>
                <thead><tr><th>ID</th><th>Label</th><th>API ID</th><th>API hash</th><th></th></tr></thead>
                <tbody id="apps-body"><tr><td colspan="5" class="muted">Loading…</td></tr></tbody>
            </table>
        </section>

        {{-- Link account: phone -> code -> 2FA --}}
        <section>
            <h2>Link Telegram account</h2>
            <div class="step active" id="step-phone">
                <div class="row">
                    <div><label for="link-app">App</label><select id="link-app"></select></div>
                    <div><label for="link-phone">Phone (E.164)</label><input id="link-phone" placeholder="+15551234567"></div>
                    <div><label for="link-label">Label (optional)</label><input id="link-label" placeholder="my-account"></div>
                    <div><label for="link-dc">DC (optional)</label><input id="link-dc" type="number" placeholder="2" value="2"></div>
                    <button id="link-send">Send code</button>
                </div>
            </div>
            <div class="step" id="step-code">
                <div class="row">
                    <div><label for="verify-code">Verification code</label><input id="verify-code" placeholder="12345"></div>
                    <button id="verify-submit">Verify</button>
                </div>
            </div>
            <div class="step" id="step-2fa">
                <div class="row">
                    <div><label for="password-input">Cloud password</label><input id="password-input" type="password"></div>
                    <button id="password-submit">Finish</button>
                </div>
            </div>
            <div class="msg" id="link-msg"></div>
        </section>

        {{-- Accounts list --}}
        <section>
            <h2>Accounts</h2>
            <table>
                <thead><tr><th>Label</th><th>Type</th><th>Telegram ID</th><th>DC</th><th>App</th><th>Session</th><th></th></tr></thead>
                <tbody id="accounts-body"><tr><td colspan="7" class="muted">Loading…</td></tr></tbody>
            </table>
        </section>
    </main>

    <script>
        (function () {
            let loginId = null;
            let pendingAppId = null;
            let pendingLabel = null;
            let pendingPhone = null;

            const msg = (el, text, ok) => { el.textContent = text; el.className = 'msg ' + (ok ? 'ok' : 'err'); };

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

            async function loadApps() {
                const data = await api('apps');
                const body = document.getElementById('apps-body');
                const sel = document.getElementById('link-app');
                body.innerHTML = '';
                sel.innerHTML = '';
                (data.apps || []).forEach((app) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + app.id + '</td><td>' + esc(app.label) + '</td><td>' + app.api_id + '</td><td>' + esc(app.api_hash) + '</td><td><button class="danger" data-id="' + app.id + '">Delete</button></td>';
                    tr.querySelector('button').addEventListener('click', () => deleteApp(app.id));
                    body.appendChild(tr);
                    const opt = document.createElement('option');
                    opt.value = app.id; opt.textContent = app.label + ' (' + app.api_id + ')';
                    sel.appendChild(opt);
                });
                if (!(data.apps || []).length) { body.innerHTML = '<tr><td colspan="5" class="muted">No apps yet — add one above.</td></tr>'; }
            }

            async function loadAccounts() {
                const data = await api('accounts');
                const body = document.getElementById('accounts-body');
                body.innerHTML = '';
                (data.accounts || []).forEach((acc) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + esc(acc.label) + '</td><td>' + esc(acc.type) + '</td><td>' + (acc.user_id || '-') + '</td><td>' + acc.dc_id + '</td><td>' + esc(acc.app_label || '-') + '</td><td>' + (acc.has_session ? 'encrypted' : '-') + '</td><td><button class="danger" data-id="' + acc.id + '">Delete</button></td>';
                    tr.querySelector('button').addEventListener('click', () => deleteAccount(acc.id));
                    body.appendChild(tr);
                });
                if (!(data.accounts || []).length) { body.innerHTML = '<tr><td colspan="7" class="muted">No linked accounts.</td></tr>'; }
            }

            async function deleteApp(id) {
                try { await api('apps/' + id, 'DELETE'); await loadApps(); await loadAccounts(); }
                catch (e) { msg(document.getElementById('app-msg'), e.message, false); }
            }

            async function deleteAccount(id) {
                if (!confirm('Delete this linked account? The session is removed.')) return;
                try { await api('accounts/' + id, 'DELETE'); await loadAccounts(); }
                catch (e) { msg(document.getElementById('link-msg'), e.message, false); }
            }

            function esc(s) { return String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }

            function step(id) {
                ['step-phone', 'step-code', 'step-2fa'].forEach((s) => document.getElementById(s).classList.toggle('active', s === id));
            }

            document.getElementById('app-submit').addEventListener('click', async () => {
                const label = document.getElementById('app-label').value.trim();
                const api_id = document.getElementById('app-api-id').value.trim();
                const api_hash = document.getElementById('app-api-hash').value.trim();
                if (!label || !api_id || !api_hash) { msg(document.getElementById('app-msg'), 'All fields required.', false); return; }
                try {
                    await api('apps', 'POST', { label, api_id: Number(api_id), api_hash });
                    document.getElementById('app-label').value = '';
                    document.getElementById('app-api-id').value = '';
                    document.getElementById('app-api-hash').value = '';
                    msg(document.getElementById('app-msg'), 'App saved.', true);
                    await loadApps();
                } catch (e) { msg(document.getElementById('app-msg'), e.message, false); }
            });

            document.getElementById('link-send').addEventListener('click', async () => {
                const app_id = Number(document.getElementById('link-app').value);
                const phone = document.getElementById('link-phone').value.trim();
                const label = document.getElementById('link-label').value.trim() || null;
                const dc_id = Number(document.getElementById('link-dc').value || 2);
                if (!app_id || !phone) { msg(document.getElementById('link-msg'), 'Choose an app and enter a phone.', false); return; }
                try {
                    const data = await api('telegram/start', 'POST', { app_id, phone, dc_id, label });
                    loginId = data.login_id; pendingAppId = app_id; pendingLabel = label; pendingPhone = phone;
                    msg(document.getElementById('link-msg'), data.message || 'Code sent.', true);
                    step('step-code');
                } catch (e) { msg(document.getElementById('link-msg'), e.message, false); }
            });

            document.getElementById('verify-submit').addEventListener('click', async () => {
                const code = document.getElementById('verify-code').value.trim();
                if (!loginId || !code) { msg(document.getElementById('link-msg'), 'Enter the code.', false); return; }
                try {
                    const data = await api('telegram/verify', 'POST', { login_id: loginId, code });
                    if (data.two_factor_required) { msg(document.getElementById('link-msg'), 'Cloud password required.', true); step('step-2fa'); return; }
                    msg(document.getElementById('link-msg'), 'Account linked.', true);
                    resetLogin();
                } catch (e) { msg(document.getElementById('link-msg'), e.message, false); }
            });

            document.getElementById('password-submit').addEventListener('click', async () => {
                const password = document.getElementById('password-input').value;
                if (!loginId || !password) { msg(document.getElementById('link-msg'), 'Enter the password.', false); return; }
                try {
                    await api('telegram/password', 'POST', { login_id: loginId, password });
                    msg(document.getElementById('link-msg'), 'Account linked.', true);
                    resetLogin();
                } catch (e) { msg(document.getElementById('link-msg'), e.message, false); }
            });

            function resetLogin() {
                loginId = null; pendingAppId = null; pendingLabel = null; pendingPhone = null;
                document.getElementById('verify-code').value = '';
                document.getElementById('password-input').value = '';
                step('step-phone');
                loadAccounts();
            }

            loadApps().catch((e) => msg(document.getElementById('app-msg'), e.message, false));
            loadAccounts().catch((e) => msg(document.getElementById('link-msg'), e.message, false));
        })();
    </script>
</body>
</html>