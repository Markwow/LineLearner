<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Script;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ScriptController extends Controller
{
    public function index(Request $request)
    {
        $query = Script::with('owner')->withCount('recordings');

        if ($request->query('owner') === 'none') {
            $query->whereNull('user_id');
        } elseif (is_numeric($request->query('owner'))) {
            $query->where('user_id', (int) $request->query('owner'));
        }

        return view('admin.scripts', [
            'scripts' => $query->latest('updated_at')->get(),
            'users' => User::orderBy('name')->get(),
            'ownerFilter' => (string) $request->query('owner', ''),
            'unownedCount' => Script::whereNull('user_id')->count(),
        ]);
    }

    /** Reassign a script (and therefore its recordings) to another user. */
    public function updateOwner(Request $request, Script $script): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        $script->update(['user_id' => $data['user_id'] ?: null]);

        $owner = $script->fresh()->owner;

        return back()->with('status', $owner
            ? "“{$script->title}” now belongs to {$owner->name}."
            : "“{$script->title}” has no owner.");
    }

    /** Bulk-assign every currently unowned script to one user. */
    public function claimUnowned(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $count = Script::whereNull('user_id')->update(['user_id' => $data['user_id']]);
        $owner = User::find($data['user_id']);

        return back()->with('status', "Assigned {$count} script(s) to {$owner->name}.");
    }

    public function destroy(Script $script): RedirectResponse
    {
        foreach ($script->recordings as $recording) {
            Storage::disk('public')->delete($recording->path);
        }

        $script->delete();

        return back()->with('status', "Deleted “{$script->title}”.");
    }
}
