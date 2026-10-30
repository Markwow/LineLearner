<?php

namespace App\Http\Controllers;

use App\Models\Script;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class RecordingController extends Controller
{
    public function store(Request $request, Script $script, int $lineIndex)
    {
        Gate::authorize('update', $script);

        $request->validate([
            'audio' => ['required', 'file', 'mimetypes:audio/webm,audio/ogg,audio/mp4,audio/x-m4a,audio/aac,audio/mpeg,video/webm,video/mp4', 'max:51200'],
        ]);

        // Replace any existing recording for this line, keeping its delay setting.
        $existing = $script->recordings()->where('line_index', $lineIndex)->first();
        $delay = $existing?->delay_seconds;
        if ($existing) {
            Storage::disk('public')->delete($existing->path);
            $existing->delete();
        }

        $path = $request->file('audio')->store("recordings/{$script->id}", 'public');

        $recording = $script->recordings()->create([
            'line_index' => $lineIndex,
            'path' => $path,
            'delay_seconds' => $delay,
        ]);

        return response()->json([
            'line_index' => $lineIndex,
            'url' => $recording->url(),
        ]);
    }

    public function updateDelay(Request $request, Script $script, int $lineIndex)
    {
        Gate::authorize('update', $script);

        $data = $request->validate([
            'delay_seconds' => ['nullable', 'numeric', 'min:0', 'max:300'],
        ]);

        $recording = $script->recordings()->where('line_index', $lineIndex)->firstOrFail();
        $delay = $data['delay_seconds'] ?? null;
        $recording->update(['delay_seconds' => $delay === null ? null : round((float) $delay, 1)]);

        return response()->json([
            'line_index' => $lineIndex,
            'delay_seconds' => $recording->delay_seconds,
        ]);
    }

    public function destroy(Script $script, int $lineIndex)
    {
        Gate::authorize('update', $script);

        $recording = $script->recordings()->where('line_index', $lineIndex)->first();

        if ($recording) {
            Storage::disk('public')->delete($recording->path);
            $recording->delete();
        }

        return response()->json(['line_index' => $lineIndex]);
    }
}
