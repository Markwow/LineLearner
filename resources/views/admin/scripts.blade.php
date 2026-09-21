@extends('layouts.app')

@section('title', 'All scripts · Line Learner')

@section('content')
    <div class="row-flex" style="justify-content:space-between; margin-bottom:14px;">
        <h2 style="margin:0">All scripts</h2>
        <a href="{{ route('scripts.index') }}" class="muted">← my scripts</a>
    </div>

    @if($unownedCount > 0)
        <div class="panel">
            <h2 style="margin-top:0; font-size:16px">{{ $unownedCount }} script(s) have no owner</h2>
            <p class="muted" style="font-size:13px; margin-top:0">
                Scripts recorded before accounts existed. Assign them all at once, or one at a time below.
            </p>
            <form method="POST" action="{{ route('admin.scripts.claim') }}" class="row-flex" style="gap:8px">
                @csrf
                <select name="user_id" required>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                <button type="submit">Assign all unowned</button>
            </form>
        </div>
    @endif

    <form method="GET" class="row-flex" style="gap:8px; margin-bottom:14px">
        <label style="margin:0">Owner</label>
        <select name="owner" onchange="this.form.submit()">
            <option value="" @selected($ownerFilter === '')>Everyone</option>
            <option value="none" @selected($ownerFilter === 'none')>No owner</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected($ownerFilter === (string) $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
    </form>

    <div class="panel">
        @if($scripts->isEmpty())
            <p class="muted" style="margin:0">No scripts here.</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>Script</th>
                        <th>Recordings</th>
                        <th>Owner</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($scripts as $script)
                        <tr>
                            <td>
                                <a href="{{ route('scripts.show', $script) }}">{{ $script->title }}</a>
                                <div class="pill">updated {{ $script->updated_at->diffForHumans() }}</div>
                            </td>
                            <td class="muted">{{ $script->recordings_count }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.scripts.owner', $script) }}" class="row-flex" style="gap:6px">
                                    @csrf
                                    @method('PUT')
                                    <select name="user_id" onchange="this.form.submit()">
                                        <option value="" @selected($script->user_id === null)>— no owner —</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" @selected($script->user_id === $user->id)>{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                    <noscript><button type="submit">Set</button></noscript>
                                </form>
                            </td>
                            <td style="text-align:right">
                                <form method="POST" action="{{ route('admin.scripts.destroy', $script) }}"
                                      onsubmit="return confirm('Delete “{{ $script->title }}” and its recordings?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
