@extends('layouts.app')

@section('title', 'Create account · Line Learner')

@section('content')
    <div class="auth-wrap">
        <div class="panel">
            <h2 style="margin-top:0">Create your account</h2>

            @if($bootstrap)
                <p class="muted" style="font-size:13px; margin-top:0">
                    No accounts exist yet, so this first sign-up becomes the admin. After that, sign-ups need an invite.
                </p>
            @elseif($invite && $invite->isRedeemable())
                <p class="muted" style="font-size:13px; margin-top:0">
                    Invite <span class="code-pill">{{ $invite->code }}</span> accepted.
                </p>
            @else
                <p class="muted" style="font-size:13px; margin-top:0">
                    Sign-up is invite-only. Enter the code an admin gave you.
                </p>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                @unless($bootstrap)
                    <label for="code">Invite code</label>
                    <input type="text" id="code" name="code"
                           value="{{ old('code', $code) }}"
                           placeholder="ABCD-EFGH" required
                           @if(! old('code') && ! $code) autofocus @endif>
                @endunless

                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required>

                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       value="{{ old('email', $invite?->email) }}" required autocomplete="username">

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">

                <label for="password_confirmation">Confirm password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">

                <button type="submit">Create account</button>
            </form>
        </div>
        <p class="muted" style="text-align:center; font-size:13px">
            Already have an account? <a href="{{ route('login') }}">Log in</a>.
        </p>
    </div>
@endsection
