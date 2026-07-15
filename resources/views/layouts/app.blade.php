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
        </header>
        @yield('content')
    </div>
</body>
</html>
