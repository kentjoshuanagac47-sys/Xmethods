<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/app-logo.png" type="image/png">
    <meta name="theme-color" content="#000000">
    <link rel="apple-touch-icon" href="/icons/app-logo.png">
    <script src="/sw-register.js" defer></script>
    <title>Invitation dashboard</title>
    <style>
        :root { color-scheme: dark; --bg: #080809; --panel: #111113; --line: #202023; --muted: #77777d; --text: #ededf0; --green: #20c982; --pink: #f42b62; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; background: var(--bg); color: var(--text); font-family: Arial, sans-serif; }
        .app-shell { display: grid; grid-template-columns: 144px minmax(0, 1fr); min-height: 100vh; }
        .sidebar { display: flex; flex-direction: column; border-right: 1px solid #171719; padding: 18px 10px 14px; }
        .brand { display: flex; align-items: center; gap: 12px; padding: 0 8px 28px; color: #f2f2f3; font-size: 12px; font-weight: 700; }
        .brand-mark { width: 16px; height: 16px; fill: currentColor; }
        .back-link { color: #85858b; text-decoration: none; font-size: 11px; }
        .nav { display: grid; gap: 6px; }
        .nav-link { display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: 5px; color: #8b8b91; font-size: 11px; text-decoration: none; }
        .nav-link.active { background: #171719; color: #eeeef0; }
        .nav-icon { width: 13px; height: 13px; border: 1px solid currentColor; border-radius: 3px; opacity: .85; }
        .sidebar-footer { margin-top: auto; padding: 12px 8px 0; border-top: 1px solid #171719; }
        .logout { padding: 0; border: 0; background: transparent; color: #818187; font: inherit; font-size: 11px; cursor: pointer; }
        .main { min-width: 0; padding: 42px clamp(22px, 5vw, 72px); }
        .topbar { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; max-width: 1000px; margin: 0 auto 26px; }
        h1 { margin: 0 0 5px; font-size: 16px; letter-spacing: -.02em; }
        .subtitle { margin: 0; color: var(--muted); font-size: 10px; }
        .invite-form { display: flex; align-items: center; gap: 6px; }
        .invite-form input { width: 58px; height: 30px; padding: 0 8px; border: 1px solid #29292d; border-radius: 5px; background: #0e0e10; color: #fff; font-size: 12px; }
        button { height: 30px; padding: 0 12px; border: 0; border-radius: 5px; background: #f0f0f2; color: #111; font-size: 11px; font-weight: 700; cursor: pointer; }
        .workspace { max-width: 1000px; margin: 0 auto; }
        .stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-bottom: 20px; }
        .stat, .records { border: 1px solid var(--line); border-radius: 10px; background: var(--panel); }
        .stat { min-height: 72px; padding: 16px; }
        .stat-label { display: block; margin-bottom: 10px; color: #a1a1a6; font-size: 9px; }
        .stat-value { font-size: 20px; font-weight: 400; }
        .stat-value.green { color: var(--green); }
        .stat-value.amber { color: #e3a313; }
        .records { overflow: hidden; }
        .records-head { display: flex; align-items: center; justify-content: space-between; padding: 15px 16px 13px; border-bottom: 1px solid var(--line); }
        .records-head h2 { margin: 0; font-size: 11px; }
        .records-count { color: #77777d; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 12px 14px; border-bottom: 1px solid #1b1b1e; font-size: 10px; }
        th { color: #6d6d73; font-size: 9px; font-weight: 400; }
        td { color: #d6d6da; }
        tbody tr:last-child td { border-bottom: 0; }
        tbody tr:hover { background: #151518; }
        .session-row { cursor: pointer; }
        .session-row:focus-visible { outline: 2px solid #f0f0f2; outline-offset: -2px; }
        .case-link { color: #e8e8eb; text-decoration: none; font-weight: 600; }
        .case-link:hover { color: #fff; }
        .code { color: #88888f; font-variant-numeric: tabular-nums; }
        .status { color: var(--green); font-weight: 600; }
        .status::before { content: ''; display: inline-block; width: 4px; height: 4px; margin-right: 5px; border-radius: 50%; background: currentColor; vertical-align: middle; }
        .status.pending { color: #e3a313; }
        .empty { padding: 22px 16px; color: var(--muted); font-size: 11px; }
        .invite { margin: 14px auto 0; max-width: 1000px; padding: 12px 14px; border: 1px solid #254d39; border-radius: 7px; background: #0d1b14; color: #c4f5d5; font-size: 10px; }
        .invite p { margin: 5px 0 0; color: #85b99a; }
        .invite code { display: block; margin-top: 6px; overflow-wrap: anywhere; }
        .error { color: #ff8d8d; font-size: 11px; }
        .modal { position: fixed; inset: 0; z-index: 10; display: grid; place-items: center; padding: 20px; background: rgba(0, 0, 0, .72); }
        .modal[hidden] { display: none; }
        .modal-card { width: min(430px, 100%); border: 1px solid #2a2a2e; border-radius: 10px; background: #121214; box-shadow: 0 24px 80px rgba(0, 0, 0, .55); }
        .modal-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 18px; border-bottom: 1px solid var(--line); }
        .modal-head h2 { margin: 0 0 5px; font-size: 15px; }
        .modal-head p { margin: 0; color: var(--muted); font-size: 10px; }
        .modal-close { width: 26px; height: 26px; padding: 0; border: 1px solid #343438; border-radius: 50%; background: transparent; color: #aaa; font-size: 16px; line-height: 1; }
        .modal-body { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; padding: 18px; }
        .detail-label { display: block; margin-bottom: 5px; color: #707077; font-size: 9px; text-transform: uppercase; letter-spacing: .05em; }
        .detail-value { color: #e4e4e7; font-size: 11px; overflow-wrap: anywhere; }
        .detail-wide { grid-column: 1 / -1; }
        .detail-message { min-height: 45px; padding: 10px; border: 1px solid #242428; border-radius: 6px; background: #0d0d0f; line-height: 1.45; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 8px; padding: 0 18px 18px; }
        .modal-actions .cancel { border: 1px solid #343438; background: transparent; color: #ccc; }
        .modal-actions .end-session { height: 30px; margin: 0; border: 1px solid #743d3d; background: transparent; color: #ffaaaa; }
        @media (max-width: 640px) {
            .app-shell { grid-template-columns: 1fr; }
            .sidebar { flex-direction: row; align-items: center; gap: 12px; border-right: 0; border-bottom: 1px solid #171719; padding: 12px; }
            .brand { padding: 0; }
            .nav { display: flex; flex: 1; }
            .nav-link { padding: 7px 8px; }
            .sidebar-footer { margin: 0 0 0 auto; padding: 0; border: 0; }
            .main { padding: 24px 14px; }
            .topbar { flex-direction: column; margin-bottom: 20px; }
            .invite-form, .invite-form input, .invite-form button { width: 100%; }
            .stats { gap: 7px; }
            .stat { padding: 12px; }
            .stat-label { font-size: 8px; }
            table { min-width: 560px; }
            .records { overflow-x: auto; }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand"><svg class="brand-mark" viewBox="0 0 24 24" aria-hidden="true"><path d="M21.7 21.75 14.18 10.57l7.06-8.32h-2.46l-5.69 6.71-4.54-6.71H2.36l7.29 10.78-7.4 10.72h2.46l6.04-7.12 4.82 7.12h6.19ZM7.74 3.82l11.07 16.36h-2.45L5.29 3.82h2.45Z"/></svg><span>Admin</span></div>
            <nav class="nav"><a class="nav-link active" href="{{ route('admin.dashboard') }}"><span class="nav-icon"></span>Overview</a><a class="nav-link" href="{{ $cases->isNotEmpty() ? route('admin.cases.show', $cases->first()) : route('admin.dashboard') }}"><span class="nav-icon"></span>Inbox</a></nav>
            <div class="sidebar-footer"><form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="logout" type="submit">Sign out</button></form></div>
        </aside>
        <main class="main">
            <header class="topbar">
                <div><h1>Overview</h1><p class="subtitle">Real-time session monitoring</p></div>
                <form class="invite-form" method="POST" action="{{ route('admin.invites.create') }}">
                    @csrf
                    <input id="hours" name="hours" type="number" min="1" max="720" value="72" aria-label="Link validity in hours" required>
                    <button type="submit">New invite</button>
                </form>
            </header>
            <div class="workspace">
                <section class="stats" aria-label="Session statistics">
                    <div class="stat"><span class="stat-label">Total</span><span class="stat-value">{{ $cases->count() }}</span></div>
                    <div class="stat"><span class="stat-label">Verified</span><span class="stat-value green">{{ $cases->where('status', 'verified')->count() }}</span></div>
                    <div class="stat"><span class="stat-label">Pending</span><span class="stat-value amber">{{ $cases->where('status', '!=', 'verified')->count() }}</span></div>
                </section>
                <section class="records">
                    <div class="records-head"><h2>Sessions</h2><span class="records-count">1 of {{ $cases->count() }}</span></div>
            @if ($cases->isEmpty())
                <p class="empty">No users have submitted a support request yet.</p>
            @else
                <table>
                    <thead><tr><th>Code</th><th>User</th><th>Email</th><th>Status</th><th>Time</th></tr></thead>
                    <tbody>
                        @foreach ($cases as $case)
                            <tr class="session-row" tabindex="0" data-user="{{ $case->username }}" data-email="{{ $case->email }}" data-new-email="{{ $case->new_email ?? 'Not provided' }}" data-status="{{ str_replace('_', ' ', ucfirst($case->status)) }}" data-time="{{ $case->created_at->format('Y-m-d H:i') }}" data-message="{{ $case->message }}" data-end-url="{{ route('admin.cases.end-session', $case) }}">
                                <td class="code">{{ str_pad((string) $case->id, 6, '0', STR_PAD_LEFT) }}</td>
                                <td><a class="case-link" href="{{ route('admin.cases.show', $case) }}">{{ '@' . ltrim($case->username, '@') }}</a></td>
                                <td>{{ $case->email }}</td>
                                <td><span class="status {{ $case->status === 'verified' ? '' : 'pending' }}">{{ ucfirst(str_replace('_', ' ', $case->status)) }}</span></td>
                                <td>{{ $case->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
                </section>
                @isset($inviteUrl)
                    <div class="invite"><strong>Invitation link generated</strong><p>Expires {{ $expiresAt->format('Y-m-d H:i T') }}.</p><code>{{ $inviteUrl }}</code></div>
                @endisset
            </div>
        </main>
    </div>
    <div class="modal" id="session-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title" hidden>
        <section class="modal-card">
            <header class="modal-head">
                <div><h2 id="modal-title">User details</h2><p id="modal-email"></p></div>
                <button class="modal-close" type="button" data-close-modal aria-label="Close details">&times;</button>
            </header>
            <div class="modal-body">
                <div><span class="detail-label">Username</span><span class="detail-value" id="modal-user"></span></div>
                <div><span class="detail-label">Status</span><span class="detail-value" id="modal-status"></span></div>
                <div><span class="detail-label">New email</span><span class="detail-value" id="modal-new-email"></span></div>
                <div><span class="detail-label">Received</span><span class="detail-value" id="modal-time"></span></div>
                <div class="detail-wide"><span class="detail-label">Original message</span><div class="detail-value detail-message" id="modal-message"></div></div>
            </div>
            <div class="modal-actions">
                <button class="cancel" type="button" data-close-modal>Cancel</button>
                <form id="modal-end-form" method="POST">
                    @csrf
                    <button class="end-session" type="submit" onclick="return confirm('End this user session? Their access will be disabled immediately.');">End session</button>
                </form>
            </div>
        </section>
    </div>
    <script>
        const sessionModal = document.querySelector('#session-modal');
        const modalEndForm = document.querySelector('#modal-end-form');

        function closeSessionModal() {
            sessionModal.hidden = true;
            document.body.style.overflow = '';
        }

        function openSessionModal(row) {
            document.querySelector('#modal-user').textContent = row.dataset.user;
            document.querySelector('#modal-email').textContent = row.dataset.email;
            document.querySelector('#modal-status').textContent = row.dataset.status;
            document.querySelector('#modal-new-email').textContent = row.dataset.newEmail;
            document.querySelector('#modal-time').textContent = row.dataset.time;
            document.querySelector('#modal-message').textContent = row.dataset.message;
            modalEndForm.action = row.dataset.endUrl;
            sessionModal.hidden = false;
            document.body.style.overflow = 'hidden';
            document.querySelector('[data-close-modal]').focus();
        }

        document.querySelectorAll('.session-row').forEach(row => {
            row.addEventListener('click', event => {
                if (event.target.closest('a, button, form')) return;
                openSessionModal(row);
            });
            row.addEventListener('keydown', event => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openSessionModal(row);
                }
            });
        });
        document.querySelectorAll('[data-close-modal]').forEach(button => button.addEventListener('click', closeSessionModal));
        sessionModal.addEventListener('click', event => {
            if (event.target === sessionModal) closeSessionModal();
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && !sessionModal.hidden) closeSessionModal();
        });

        function escapeMessage(value) {
            return String(value).replace(/[&<>'"]/g, character => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
            }[character]));
        }

        function renderThread(thread, messages) {
            if (!messages.length) {
                thread.innerHTML = '<span class="empty">No messages yet.</span>';
                return;
            }

            thread.innerHTML = messages.map(message => `<div class="bubble ${message.sender === 'admin' ? 'admin' : ''}"><strong>${escapeMessage(message.sender.charAt(0).toUpperCase() + message.sender.slice(1))}:</strong> ${escapeMessage(message.body)}<small>${escapeMessage(message.created_at)}</small></div>`).join('');
            thread.scrollTop = thread.scrollHeight;
        }

        async function pollThreads() {
            await Promise.all(Array.from(document.querySelectorAll('[data-poll-url]')).map(async thread => {
                try {
                    const response = await fetch(thread.dataset.pollUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                    if (response.ok) renderThread(thread, await response.json());
                    const typingResponse = await fetch(thread.dataset.typingUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                    if (typingResponse.ok) {
                        const indicator = thread.previousElementSibling;
                        indicator.innerHTML = (await typingResponse.json()).typing ? '<span></span><span></span><span></span>' : '';
                        indicator.classList.toggle('is-visible', indicator.innerHTML !== '');
                    }
                } catch (error) {
                    // Keep the current conversation visible if polling is temporarily unavailable.
                }
            }));
        }

        const dashboardTypingToken = document.querySelector('input[name="_token"]').value;
        document.querySelectorAll('.reply-form input').forEach(input => {
            let lastTypingSignal = 0;
            input.addEventListener('input', () => {
                const now = Date.now();
                if (now - lastTypingSignal < 1500) return;
                lastTypingSignal = now;
                const thread = input.closest('td').querySelector('[data-thread]');
                fetch(thread.dataset.typingUrl.replace('/typing', '/typing'), { method: 'POST', headers: { 'X-CSRF-TOKEN': dashboardTypingToken, Accept: 'application/json' }, credentials: 'same-origin' });
            });
        });

        pollThreads();
        window.setInterval(pollThreads, 3000);
    </script>
</body>
</html>
