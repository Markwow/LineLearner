@extends('layouts.app')

@section('title', 'Log in · Line Learner')

@section('content')
    <div class="auth-wrap">
        <div class="panel">
            <h2 style="margin-top:0">Log in</h2>
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">

                <div class="checkline">
                    <input type="checkbox" id="remember" name="remember" value="1">
                    <label for="remember" style="margin:0; color:var(--text)">Stay logged in</label>
                </div>

                <button type="submit">Log in</button>
            </form>
        </div>
        <p class="hint muted" style="text-align:center; font-size:13px">
            Got an invite? <a href="{{ route('register') }}">Create your account</a>.
        </p>
    </div>
@endsection
