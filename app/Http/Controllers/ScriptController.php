<?php

namespace App\Http\Controllers;

use App\Models\Script;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ScriptController extends Controller
{
    public function index()
    {
        $scripts = Script::withCount('recordings')->latest()->get();

        return view('scripts.index', compact('scripts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $script = Script::create($data);

        return redirect()->route('scripts.show', $script);
    }

    public function show(Script $script)
    {
        $script->load('recordings');

        return view('scripts.show', [
            'script' => $script,
            'lines' => $script->lines(),
            'speakers' => $script->speakers(),
            'recordingUrls' => $script->recordingUrls(),
        ]);
    }

    public function update(Request $request, Script $script)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $script->update($data);

        return redirect()->route('scripts.show', $script);
    }

    public function destroy(Script $script)
    {
        foreach ($script->recordings as $recording) {
            Storage::disk('public')->delete($recording->path);
        }

        $script->delete();

        return redirect()->route('scripts.index');
    }
}
