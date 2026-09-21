<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users', [
            'users' => User::withCount('scripts')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_admin' => $request->boolean('is_admin'),
        ]);

        return back()->with('status', "Created account for {$data['name']}.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', Password::min(8)],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        $isAdmin = $request->boolean('is_admin');

        // Don't let the last admin (or yourself) lock everyone out of the admin area.
        if ($user->isAdmin() && ! $isAdmin && $this->adminCount() <= 1) {
            return back()->withErrors(['is_admin' => 'This is the only admin — promote someone else first.']);
        }

        if ($user->id === $request->user()->id && ! $isAdmin) {
            return back()->withErrors(['is_admin' => 'You cannot remove your own admin access.']);
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_admin' => $isAdmin,
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return back()->with('status', "Updated {$user->name}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        if ($user->isAdmin() && $this->adminCount() <= 1) {
            return back()->withErrors(['user' => 'You cannot delete the only admin.']);
        }

        $mode = $request->input('scripts', 'keep');

        if ($mode === 'delete') {
            foreach ($user->scripts()->with('recordings')->get() as $script) {
                foreach ($script->recordings as $recording) {
                    Storage::disk('public')->delete($recording->path);
                }
                $script->delete();
            }
        } elseif ($mode === 'reassign') {
            $newOwnerId = $request->validate([
                'new_owner_id' => ['required', 'exists:users,id'],
            ])['new_owner_id'];

            $user->scripts()->update(['user_id' => $newOwnerId]);
        }
        // 'keep' leaves the scripts owner-less; the FK nulls them out on delete.

        $name = $user->name;
        $user->delete();

        return back()->with('status', "Deleted {$name}.");
    }

    protected function adminCount(): int
    {
        return User::where('is_admin', true)->count();
    }
}
