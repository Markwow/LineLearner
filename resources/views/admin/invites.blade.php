@extends('layouts.app')

@section('title', 'Invites · Line Learner')

@section('content')
    <div class="row-flex" style="justify-content:space-between; margin-bottom:14px;">
        <h2 style="margin:0">Invites</h2>
        <a href="{{ route('scripts.index') }}" class="muted">← my scripts</a>
    </div>

    <div class="panel">
        <h2 style="margin-top:0; font-size:16px">Create an invite</h2>
        <p class="muted" style="font-size:13px; margin-top:0">
            Nobody can register without one. Send them the link — it fills the code in for them.
        </p>
        <form method="POST" action="{{ route('admin.invites.store') }}">
            @csrf
            <div class="grid-2">
                <div>
                    <label>Lock to email <span class="muted">(optional)</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="them@example.com">
                </div>
                <div>
                    <label>Expires in days <span class="muted">(optional)</span></label>
                    <input type="number" name="expires_in_days" value="{{ old('expires_in_days') }}" min="1" max="365" placeholder="never">
                </div>
            </div>
            <label>Note <span class="muted">(optional — who is this for?)</span></label>
            <input type="text" name="note" value="{{ old('note') }}" placeholder="Ivan, Tuesday rehearsal">
            <button type="submit">Generate invite</button>
        </form>
    </div>

    <div class="panel">
        @if($invites->isEmpty())
            <p class="muted" style="margin:0">No invites yet.</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>For</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invites as $invite)
                        <tr @if(session('new_invite') === $invite->code) style="background:rgba(108,140,255,.08)" @endif>
                            <td>
                                <span class="code-pill">{{ $invite->code }}</span>
                                @if($invite->isRedeemable())
                                    <div style="margin-top:6px">
                                        <button type="button" class="btn-ghost" style="font-size:12px; padding:4px 10px"
                                                data-url="{{ $invite->signupUrl() }}"
                                                onclick="copyInvite(this)">Copy link</button>
                                    </div>
                                @endif
                            </td>
                            <td class="muted">
                                {{ $invite->email ?: '—' }}
                                @if($invite->note)<div class="pill">{{ $invite->note }}</div>@endif
                            </td>
                            <td>
                                @if($invite->isUsed())
                                    <span class="tag dim">Used</span>
                                    <div class="pill">{{ $invite->redeemer?->name ?? 'deleted user' }} · {{ $invite->used_at->diffForHumans() }}</div>
                                @elseif($invite->isExpired())
                                    <span class="tag warn">Expired</span>
                                @else
                                    <span class="tag good">Open</span>
                                    @if($invite->expires_at)
                                        <div class="pill">expires {{ $invite->expires_at->diffForHumans() }}</div>
                                    @endif
                                @endif
                            </td>
                            <td style="text-align:right">
                                <form method="POST" action="{{ route('admin.invites.destroy', $invite) }}"
                                      onsubmit="return confirm('Revoke this invite?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger">{{ $invite->isRedeemable() ? 'Revoke' : 'Remove' }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <script>
        function copyInvite(btn) {
            const url = btn.dataset.url;
            const done = () => { btn.textContent = 'Copied!'; setTimeout(() => btn.textContent = 'Copy link', 1500); };
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(done, () => prompt('Copy this invite link:', url));
            } else {
                prompt('Copy this invite link:', url);
            }
        }
    </script>
@endsection
