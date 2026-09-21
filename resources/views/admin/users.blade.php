@extends('layouts.app')

@section('title', 'Users · Line Learner')

@section('content')
    <div class="row-flex" style="justify-content:space-between; margin-bottom:14px;">
        <h2 style="margin:0">Users</h2>
        <a href="{{ route('scripts.index') }}" class="muted">← my scripts</a>
    </div>

    <div class="panel">
        <table class="data">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Scripts</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td class="muted">{{ $user->email }}</td>
                        <td>
                            @if($user->isAdmin())
                                <span class="tag good">Admin</span>
                            @else
                                <span class="tag dim">Member</span>
                            @endif
                        </td>
                        <td class="muted">
                            <a href="{{ route('admin.scripts.index', ['owner' => $user->id]) }}">{{ $user->scripts_count }}</a>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" style="padding-top:0">
                            <details>
                                <summary class="muted" style="cursor:pointer; font-size:13px">Edit {{ $user->name }}</summary>
                                <form method="POST" action="{{ route('admin.users.update', $user) }}" style="padding:8px 0 4px">
                                    @csrf
                                    @method('PUT')
                                    <div class="grid-2">
                                        <div>
                                            <label>Name</label>
                                            <input type="text" name="name" value="{{ $user->name }}" required>
                                        </div>
                                        <div>
                                            <label>Email</label>
                                            <input type="email" name="email" value="{{ $user->email }}" required>
                                        </div>
                                    </div>
                                    <label>New password <span class="muted">(leave blank to keep)</span></label>
                                    <input type="password" name="password" autocomplete="new-password">
                                    <div class="checkline">
                                        <input type="checkbox" id="admin-{{ $user->id }}" name="is_admin" value="1" @checked($user->isAdmin())>
                                        <label for="admin-{{ $user->id }}" style="margin:0">Administrator</label>
                                    </div>
                                    <button type="submit">Save</button>
                                </form>

                                @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                          onsubmit="return confirm('Delete {{ $user->name }}?')"
                                          style="border-top:1px solid var(--border); padding-top:12px; margin-top:4px">
                                        @csrf
                                        @method('DELETE')
                                        <label>Their {{ $user->scripts_count }} script(s)</label>
                                        <div class="row-flex" style="flex-wrap:wrap; gap:8px">
                                            <select name="scripts" onchange="this.closest('form').querySelector('[name=new_owner_id]').style.display = this.value === 'reassign' ? '' : 'none'">
                                                <option value="keep">Leave unowned (admin can reassign later)</option>
                                                <option value="reassign">Give to another user</option>
                                                <option value="delete">Delete scripts and recordings</option>
                                            </select>
                                            <select name="new_owner_id" style="display:none">
                                                @foreach($users->where('id', '!=', $user->id) as $other)
                                                    <option value="{{ $other->id }}">{{ $other->name }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn-danger">Delete user</button>
                                        </div>
                                    </form>
                                @endif
                            </details>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="panel">
        <h2 style="margin-top:0; font-size:16px">Add a user directly</h2>
        <p class="muted" style="font-size:13px; margin-top:0">
            Or send them an <a href="{{ route('admin.invites.index') }}">invite</a> and let them set their own password.
        </p>
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <div class="grid-2">
                <div>
                    <label>Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required>
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required>
                </div>
            </div>
            <label>Password</label>
            <input type="password" name="password" required autocomplete="new-password">
            <div class="checkline">
                <input type="checkbox" id="new-admin" name="is_admin" value="1">
                <label for="new-admin" style="margin:0">Administrator</label>
            </div>
            <button type="submit">Create user</button>
        </form>
    </div>
@endsection
