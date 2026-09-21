<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InviteController extends Controller
{
    public function index()
    {
        return view('admin.invites', [
            'invites' => Invite::with(['creator', 'redeemer'])->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $invite = Invite::create([
            'code' => Invite::generateCode(),
            'email' => $data['email'] ?? null,
            'note' => $data['note'] ?? null,
            'created_by' => $request->user()->id,
            'expires_at' => ! empty($data['expires_in_days'])
                ? now()->addDays((int) $data['expires_in_days'])
                : null,
        ]);

        return back()->with('status', "Invite {$invite->code} created.")->with('new_invite', $invite->code);
    }

    public function destroy(Invite $invite): RedirectResponse
    {
        $invite->delete();

        return back()->with('status', 'Invite revoked.');
    }
}
