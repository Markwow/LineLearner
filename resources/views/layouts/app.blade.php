<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Line Learner')</title>
    <style>
        :root {
            --bg: #0f1115;
            --panel: #191d26;
            --panel-2: #222735;
            --text: #e8ebf2;
            --muted: #8b93a7;
            --accent: #6c8cff;
            --accent-2: #4b6bff;
            --record: #ff5468;
            --good: #3fcf8e;
            --border: #2a3040;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
        }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .wrap { max-width: 780px; margin: 0 auto; padding: 24px 20px 80px; }
        header.top { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 24px; }
        header.top h1 { font-size: 20px; margin: 0; }
        header.top .sub { color: var(--muted); font-size: 13px; }
        h1, h2 { font-weight: 650; }
        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 18px;
        }
        label { display: block; font-size: 13px; color: var(--muted); margin-bottom: 6px; }
        input[type=text], textarea {
            width: 100%;
            background: var(--panel-2);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            padding: 10px 12px;
            font-size: 15px;
            font-family: inherit;
            margin-bottom: 14px;
        }
        textarea { min-height: 160px; resize: vertical; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: var(--accent); }
        button, .btn {
            font-family: inherit;
            font-size: 14px;
            border: none;
            border-radius: 10px;
            padding: 9px 16px;
            cursor: pointer;
            background: var(--accent-2);
            color: white;
            font-weight: 600;
        }
        button:hover { filter: brightness(1.08); }
        button:disabled { opacity: .5; cursor: not-allowed; }
        .btn-ghost { background: var(--panel-2); color: var(--text); border: 1px solid var(--border); }
        .btn-danger { background: transparent; color: var(--record); border: 1px solid transparent; }
        .btn-danger:hover { text-decoration: underline; }
        .row-flex { display: flex; gap: 10px; align-items: center; }
        .muted { color: var(--muted); }
        .script-list a.card {
            display: flex; justify-content: space-between; align-items: center;
            padding: 14px 16px; background: var(--panel); border: 1px solid var(--border);
            border-radius: 12px; margin-bottom: 10px; color: var(--text);
        }
        .script-list a.card:hover { border-color: var(--accent); text-decoration: none; }
        .pill { font-size: 12px; color: var(--muted); }
        select {
            background: var(--panel-2); color: var(--text); border: 1px solid var(--border);
            border-radius: 10px; padding: 8px 10px; font-size: 14px; font-family: inherit;
        }
        nav.user-nav { display: flex; gap: 14px; align-items: center; font-size: 13px; flex-wrap: wrap; justify-content: flex-end; }
        nav.user-nav .who { color: var(--muted); }
        nav.user-nav form { margin: 0; }
        nav.user-nav button.linkish {
            background: none; border: none; color: var(--accent); padding: 0;
            font-size: 13px; font-weight: 500; cursor: pointer;
        }
        nav.user-nav button.linkish:hover { text-decoration: underline; }
        .flash {
            background: rgba(63,207,142,.12); border: 1px solid rgba(63,207,142,.4);
            color: var(--good); border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; font-size: 14px;
        }
        .errors {
            background: rgba(255,84,104,.1); border: 1px solid rgba(255,84,104,.4);
            color: var(--record); border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; font-size: 14px;
        }
        .errors ul { margin: 0; padding-left: 18px; }
        table.data { width: 100%; border-collapse: collapse; font-size: 14px; }
        table.data th {
            text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .06em;
            color: var(--muted); font-weight: 600; padding: 6px 8px; border-bottom: 1px solid var(--border);
        }
        table.data td { padding: 10px 8px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        table.data tr:last-child td { border-bottom: none; }
        .tag {
            display: inline-block; font-size: 11px; font-weight: 600; letter-spacing: .04em;
            padding: 2px 8px; border-radius: 999px; border: 1px solid var(--border); color: var(--muted);
        }
        .tag.good { color: var(--good); border-color: rgba(63,207,142,.4); }
        .tag.warn { color: #e6b455; border-color: rgba(230,180,85,.4); }
        .tag.dim { opacity: .7; }
        .code-pill {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 14px;
            background: var(--panel-2); border: 1px solid var(--border); border-radius: 8px; padding: 3px 8px;
        }
        input[type=email], input[type=password], input[type=number] {
            width: 100%; background: var(--panel-2); border: 1px solid var(--border);
            border-radius: 10px; color: var(--text); padding: 10px 12px; font-size: 15px;
            font-family: inherit; margin-bottom: 14px;
        }
        .auth-wrap { max-width: 400px; margin: 60px auto 0; }
        .checkline { display: flex; align-items: center; gap: 8px; margin-bottom: 14px; font-size: 14px; color: var(--text); }
        .checkline input { margin: 0; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 12px; }
        @media (max-width: 520px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>
    @stack('head')
</head>
<body>
    <div class="wrap">
        <header class="top">
            <div>
                <h1><a href="{{ route('scripts.index') }}" style="color:var(--text)">🎭 Line Learner</a></h1>
                <div class="sub">Record the cue lines, play them back, rehearse.</div>
            </div>
            @auth
                <nav class="user-nav">
                    <span class="who">{{ auth()->user()->name }}</span>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.users.index') }}">Users</a>
                        <a href="{{ route('admin.scripts.index') }}">All scripts</a>
                        <a href="{{ route('admin.invites.index') }}">Invites</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="linkish">Log out</button>
                    </form>
                </nav>
            @endauth
        </header>

        @if(session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="errors">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>
</body>
</html>
