@extends('layouts.app')

@section('title', 'Line Learner')

@section('content')
    <div class="panel">
        <h2 style="margin-top:0">New script</h2>
        <form method="POST" action="{{ route('scripts.store') }}">
            @csrf
            <label for="title">Title</label>
            <input type="text" id="title" name="title" placeholder="Scene 3 — Mark & Ivan" value="{{ old('title') }}" required>

            <label for="body">Script (one line per row, "Speaker: line")</label>
            <textarea id="body" name="body" placeholder="Mark: hi&#10;Ivan: hey&#10;Mark: sup&#10;Ivan: nothin" required>{{ old('body') }}</textarea>

            <button type="submit">Create &amp; rehearse →</button>
        </form>
        @error('title') <p style="color:var(--record)">{{ $message }}</p> @enderror
        @error('body') <p style="color:var(--record)">{{ $message }}</p> @enderror
    </div>

    @if($scripts->isNotEmpty())
        <h2>Your scripts</h2>
        <div class="script-list">
            @foreach($scripts as $script)
                <a class="card" href="{{ route('scripts.show', $script) }}">
                    <span>{{ $script->title }}</span>
                    <span class="pill">{{ $script->recordings_count }} recorded · {{ $script->updated_at->diffForHumans() }}</span>
                </a>
            @endforeach
        </div>
    @endif
@endsection
