<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invite;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function show(Request $request)
    {
        $code = trim((string) $request->query('code', ''));

        return view('auth.register', [
            'code' => $code,
            'invite' => $code !== '' ? $this->findInvite($code) : null,
            'bootstrap' => $this->isBootstrap(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $bootstrap = $this->isBootstrap();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'code' => [$bootstrap ? 'nullable' : 'required', 'string', 'max:64'],
        ]);

        $invite = null;

        if (! $bootstrap) {
            $invite = $this->findInvite($data['code']);

            if (! $invite || ! $invite->isRedeemable()) {
                throw ValidationException::withMessages([
                    'code' => 'That invite code is not valid, has expired, or has already been used.',
                ]);
            }

            if ($invite->email && strcasecmp($invite->email, $data['email']) !== 0) {
                throw ValidationException::withMessages([
                    'email' => 'This invite was issued for a different email address.',
                ]);
            }
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            // The very first account bootstraps the admin, since nobody exists
            // yet to hand out an invite.
            'is_admin' => $bootstrap,
        ]);

        $invite?->update(['used_by' => $user->id, 'used_at' => now()]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('scripts.index');
    }

    protected function findInvite(string $code): ?Invite
    {
        return Invite::whereRaw('UPPER(code) = ?', [strtoupper(trim($code))])->first();
    }

    /** No users yet — let the first sign-up through and make them the admin. */
    protected function isBootstrap(): bool
    {
        return User::count() === 0;
    }
}
