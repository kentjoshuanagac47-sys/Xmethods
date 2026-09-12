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
    <title>User details</title>
    <style>
        :root { color-scheme: dark; --bg: #080809; --panel: #111113; --line: #202023; --muted: #77777d; --text: #ededf0; --pink: #f42b62; }
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
        .nav-link.inbox-link { position: relative; }
        .bell { width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-width: 1.5; }
        .notification-count { min-width: 14px; height: 14px; padding: 0 4px; border-radius: 999px; background: var(--pink); color: #fff; font-size: 8px; line-height: 14px; text-align: center; }
        .sidebar-footer { margin-top: auto; padding: 12px 8px 0; border-top: 1px solid #171719; }
        .logout { padding: 0; border: 0; background: transparent; color: #818187; font: inherit; font-size: 11px; cursor: pointer; }
        .main { min-width: 0; padding: 38px 22px 22px; }
        .inbox { display: grid; grid-template-columns: 178px minmax(0, 1fr); width: min(1050px, 100%); height: calc(100vh - 60px); min-height: 560px; margin: 0 auto; border: 1px solid #1c1c1f; border-radius: 10px; overflow: hidden; background: var(--panel); }
        .conversation-list { border-right: 1px solid var(--line); }
        .inbox-title { padding: 15px 14px 13px; border-bottom: 1px solid var(--line); }
        .inbox-title h1 { margin: 0 0 4px; font-size: 14px; }
        .inbox-title p { margin: 0; color: var(--muted); font-size: 9px; }
        .conversation { display: flex; align-items: center; gap: 9px; padding: 12px 10px; border-bottom: 1px solid #1b1b1e; background: #151517; }
        .avatar { display: grid; width: 27px; height: 27px; flex: 0 0 auto; place-items: center; border-radius: 50%; background: #2a2a2d; color: #bcbcc1; font-size: 10px; font-weight: 700; }
        .conversation strong { display: block; overflow: hidden; color: #e2e2e5; font-size: 10px; text-overflow: ellipsis; white-space: nowrap; }
        .conversation span { display: block; margin-top: 3px; overflow: hidden; color: #77777d; font-size: 9px; text-overflow: ellipsis; white-space: nowrap; }
        .unread { width: 6px; height: 6px; margin-left: auto; border-radius: 50%; background: var(--pink); }
        .chat { display: grid; grid-template-rows: 57px minmax(0, 1fr) 54px; min-width: 0; }
        .chat-header { display: flex; align-items: center; justify-content: space-between; padding: 0 18px; border-bottom: 1px solid var(--line); }
        .chat-header h2 { margin: 0 0 4px; font-size: 12px; }
        .chat-header p { margin: 0; color: var(--muted); font-size: 9px; }
        .back { color: #85858b; font-size: 10px; text-decoration: none; }
        .thread { display: flex; flex-direction: column; gap: 10px; min-height: 0; padding: 18px; overflow-y: auto; }
        .bubble { align-self: flex-start; max-width: 72%; padding: 10px 12px; border-radius: 9px 9px 9px 2px; background: #1a1a1c; color: #d8d8dc; font-size: 11px; line-height: 1.45; overflow-wrap: anywhere; }
        .bubble.admin { align-self: flex-end; border-radius: 9px 9px 2px 9px; background: #183520; }
        .bubble small { display: block; margin-top: 5px; color: #77777d; font-size: 9px; }
        .typing-indicator { display: none; align-self: flex-start; flex: 0 0 auto; align-items: center; justify-content: center; gap: 3px; width: 42px; height: 24px; margin: 0; border-radius: 999px; background: #2b2b2d; }
        .typing-indicator.is-visible { display: flex; }
        .typing-indicator span { width: 4px; height: 4px; border-radius: 50%; background: #aaa; animation: typing-dot 1.2s infinite ease-in-out; }
        .typing-indicator span:nth-child(2) { animation-delay: .15s; }
        .typing-indicator span:nth-child(3) { animation-delay: .3s; }
        @keyframes typing-dot { 0%, 60%, 100% { opacity: .3; transform: translateY(0); } 30% { opacity: 1; transform: translateY(-2px); } }
        .reply { display: flex; gap: 8px; align-items: center; margin: 0 12px 12px; padding: 0 10px; border: 1px solid #29292d; border-radius: 7px; background: #0e0e10; }
        .reply input { flex: 1; min-width: 0; height: 38px; padding: 0; border: 0; outline: 0; background: transparent; color: #fff; font-size: 12px; }
        .reply input[type="file"] { display: none; }
        .attach { display: grid; width: 22px; height: 22px; place-items: center; color: #aaa; cursor: pointer; font-size: 17px; }
        .attachment-selection { display: none; align-items: center; gap: 8px; margin: 0 12px 6px; color: #aaa; font-size: 10px; }
        .attachment-selection.is-visible { display: flex; }
        .attachment-selection img, .attachment-selection video { width: 38px; height: 38px; object-fit: cover; border-radius: 6px; background: #000; }
        .attachment-selection span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .reply button { height: 28px; padding: 0 11px; border: 0; border-radius: 5px; background: #e9e9eb; color: #111; font-size: 10px; font-weight: 700; }
        .attachment-preview { display: block; max-width: min(260px, 100%); max-height: 220px; margin-top: 8px; border-radius: 8px; }
        video.attachment-preview { background: #000; }
        .end-session { height: 28px; margin: 0 18px 12px; padding: 0 10px; border: 1px solid #743d3d; border-radius: 5px; background: transparent; color: #ffaaaa; font-size: 10px; }
        .details { display: none; }
        .error { margin: 0 18px 12px; color: #ff8d8d; font-size: 10px; }
        .empty { color: #77777d; font-size: 11px; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
        @media (max-width: 680px) {
            .app-shell { grid-template-columns: 1fr; }
            .sidebar { flex-direction: row; align-items: center; gap: 12px; border-right: 0; border-bottom: 1px solid #171719; padding: 12px; }
            .brand { padding: 0; }
            .nav { display: flex; flex: 1; }
            .nav-link { padding: 7px 8px; }
            .sidebar-footer { margin: 0 0 0 auto; padding: 0; border: 0; }
            .main { padding: 12px; }
            .inbox { grid-template-columns: 1fr; height: calc(100vh - 96px); min-height: 480px; }
            .conversation-list { border-right: 0; border-bottom: 1px solid var(--line); }
            .conversation { padding: 9px 12px; }
            .chat { min-height: 360px; }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand"><svg class="brand-mark" viewBox="0 0 24 24" aria-hidden="true"><path d="M21.7 21.75 14.18 10.57l7.06-8.32h-2.46l-5.69 6.71-4.54-6.71H2.36l7.29 10.78-7.4 10.72h2.46l6.04-7.12 4.82 7.12h6.19ZM7.74 3.82l11.07 16.36h-2.45L5.29 3.82h2.45Z"/></svg><span>Admin</span></div>
            <nav class="nav"><a class="nav-link" href="{{ route('admin.dashboard') }}"><span class="nav-icon"></span>Overview</a><a class="nav-link active inbox-link" href="{{ route('admin.cases.show', $supportCase) }}"><svg class="bell" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>Inbox @if ($unreadCount > 0)<span class="notification-count" aria-label="{{ $unreadCount }} unread conversations">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>@endif</a></nav>
            <div class="sidebar-footer"><form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="logout" type="submit">Sign out</button></form></div>
        </aside>
        <main class="main">
            <section class="inbox">
                <aside class="conversation-list">
                    <div class="inbox-title"><h1>Inbox <span class="sr-only">User details</span></h1><p>1 current message</p><span class="sr-only">Full case details.</span></div>
                    <div class="conversation"><div class="avatar">{{ strtoupper(substr($supportCase->username, 0, 1)) }}</div><div><strong>{{ '@' . ltrim($supportCase->username, '@') }}</strong><span>{{ $supportCase->email }}</span></div><span class="unread"></span></div>
                </aside>
                <section class="chat">
                    <header class="chat-header"><div><h2>{{ '@' . ltrim($supportCase->username, '@') }}</h2><p>{{ $supportCase->email }}</p></div><a class="back" href="{{ route('admin.dashboard') }}">Back</a></header>
                    <div class="thread" data-thread>
                @forelse ($supportCase->messages as $message)
                    <div class="bubble {{ $message->sender === 'admin' ? 'admin' : '' }}">{{ $message->body }}@if ($message->attachment_path) @if (str_starts_with($message->attachment_mime, 'image/'))<img class="attachment-preview" src="{{ route('admin.messages.attachment', [$supportCase, $message]) }}" alt="Image attachment">@else<video class="attachment-preview" src="{{ route('admin.messages.attachment', [$supportCase, $message]) }}" controls preload="metadata"></video>@endif @endif<small>{{ ucfirst($message->sender) }} · {{ $message->created_at->format('Y-m-d H:i') }}</small></div>
                @empty
                    <p class="empty">No messages yet.</p>
                @endforelse
                    <div class="typing-indicator" data-typing-indicator aria-live="polite"></div>
                    </div>
                    <div>
                        <div class="attachment-selection" data-attachment-selection aria-live="polite"></div>
                        <form class="reply" method="POST" action="{{ route('admin.messages.store', $supportCase) }}" enctype="multipart/form-data">
                @csrf
                    <label class="attach" for="admin-attachment" aria-label="Add an image or video">+</label>
                    <input id="admin-attachment" name="attachment" type="file" accept="image/*,video/*">
                    <input name="body" type="text" maxlength="4000" placeholder="Reply to user">
                            <button type="submit">Send</button>
                        </form>
            @error('body')<p class="error">{{ $message }}</p>@enderror
            @if ($supportCase->access_enabled)
                <form method="POST" action="{{ route('admin.cases.end-session', $supportCase) }}" onsubmit="return confirm('End this user session? Their access will be disabled immediately.');">
                    @csrf
                    <button class="end-session" type="submit">End user session</button>
                </form>
            @else
                <p class="error">This user session has ended. Their access is disabled.</p>
            @endif
                    </div>
                </section>
            </section>
        </main>
    </div>
    <script>
        const messageThread = document.querySelector('[data-thread]');
        const messagePollUrl = @json(route('admin.messages.index', $supportCase));
        const typingStatusUrl = @json(route('admin.typing.show', $supportCase));
        const typingStoreUrl = @json(route('admin.typing.store', $supportCase));
        const typingToken = document.querySelector('input[name="_token"]').value;
        const attachmentInput = document.querySelector('#admin-attachment');
        const attachmentSelection = document.querySelector('[data-attachment-selection]');
        let attachmentPreviewUrl = null;

        function clearAttachmentPreview() {
            if (attachmentPreviewUrl) URL.revokeObjectURL(attachmentPreviewUrl);
            attachmentPreviewUrl = null;
            attachmentSelection.innerHTML = '';
            attachmentSelection.classList.remove('is-visible');
        }

        attachmentInput.addEventListener('change', () => {
            clearAttachmentPreview();
            const file = attachmentInput.files[0];
            if (!file) return;
            attachmentPreviewUrl = URL.createObjectURL(file);
            const preview = document.createElement(file.type.startsWith('video/') ? 'video' : 'img');
            preview.src = attachmentPreviewUrl;
            if (preview.tagName === 'VIDEO') {
                preview.muted = true;
                preview.autoplay = true;
                preview.loop = true;
            }
            const label = document.createElement('span');
            label.textContent = file.name;
            attachmentSelection.append(preview, label);
            attachmentSelection.classList.add('is-visible');
        });

        function escapeMessage(value) {
            return String(value).replace(/[&<>'"]/g, character => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
            }[character]));
        }

        function renderMessages(messages) {
            if (!messages.length) {
                messageThread.innerHTML = '<p class="empty">No messages yet.</p>';
                return;
            }

            messageThread.innerHTML = messages.map(message => `<div class="bubble ${message.sender === 'admin' ? 'admin' : ''}">${escapeMessage(message.body)}${renderAttachment(message)}<small>${escapeMessage(message.sender.charAt(0).toUpperCase() + message.sender.slice(1))} · ${escapeMessage(message.created_at)}</small></div>`).join('');
            messageThread.scrollTop = messageThread.scrollHeight;
        }

        function renderAttachment(message) {
            if (!message.attachment_url) return '';
            const url = escapeMessage(message.attachment_url);
            return message.attachment_type.startsWith('image/')
                ? `<img class="attachment-preview" src="${url}" alt="Image attachment">`
                : `<video class="attachment-preview" src="${url}" controls preload="metadata"></video>`;
        }

        async function pollMessages() {
            try {
                const response = await fetch(messagePollUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (response.ok) renderMessages(await response.json());
            } catch (error) {
                // Keep the current conversation visible if polling is temporarily unavailable.
            }
        }

        async function pollTyping() {
            try {
                const response = await fetch(typingStatusUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (response.ok) {
                    const indicator = document.querySelector('[data-typing-indicator]');
                    indicator.innerHTML = (await response.json()).typing ? '<span></span><span></span><span></span>' : '';
                    indicator.classList.toggle('is-visible', indicator.innerHTML !== '');
                }
            } catch (error) {
                // Keep the current indicator state if polling is temporarily unavailable.
            }
        }

        let lastTypingSignal = 0;
        document.querySelector('.reply input[name="body"]').addEventListener('input', () => {
            const now = Date.now();
            if (now - lastTypingSignal < 1500) return;
            lastTypingSignal = now;
            fetch(typingStoreUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': typingToken, Accept: 'application/json' }, credentials: 'same-origin' });
        });

        pollMessages();
        pollTyping();
        window.setInterval(pollMessages, 3000);
        window.setInterval(pollTyping, 1000);
    </script>
</body>
</html>
