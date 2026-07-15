<?php

namespace App\Http\Controllers;

use App\Models\Script;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RecordingController extends Controller
{
    public function store(Request $request, Script $script, int $lineIndex)
    {
        $request->validate([
            'audio' => ['required', 'file', 'mimetypes:audio/webm,audio/ogg,audio/mp4,audio/mpeg,video/webm', 'max:51200'],
        ]);

        // Replace any existing recording for this line.
        $existing = $script->recordings()->where('line_index', $lineIndex)->first();
        if ($existing) {
            Storage::disk('public')->delete($existing->path);
            $existing->delete();
        }

        $path = $request->file('audio')->store("recordings/{$script->id}", 'public');

        $recording = $script->recordings()->create([
            'line_index' => $lineIndex,
            'path' => $path,
        ]);

        return response()->json([
            'line_index' => $lineIndex,
            'url' => $recording->url(),
        ]);
    }

    public function destroy(Script $script, int $lineIndex)
    {
        $recording = $script->recordings()->where('line_index', $lineIndex)->first();

        if ($recording) {
            Storage::disk('public')->delete($recording->path);
            $recording->delete();
        }

        return response()->json(['line_index' => $lineIndex]);
    }
}
