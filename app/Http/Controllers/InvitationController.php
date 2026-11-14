<?php

namespace App\Http\Controllers;

use App\Enums\MerchantStatus;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Accepts a team invitation from the link in the email. A new person sets their
 * own name and password; someone who already has an account joins and signs in.
 */
class InvitationController extends Controller
{
    public function show(string $token): View
    {
        return view('invitations.accept', [
            'invitation' => $this->usable($token),
            'token' => $token,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->usable($token);

        $existing = User::query()->where('email', $invitation->email)->first();

        if ($existing === null) {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:60'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $user = User::create([
                'name' => $data['name'],
                'email' => $invitation->email,
                'password' => Hash::make($data['password']),
            ]);
        } else {
            $user = $existing;
        }

        $invitation->merchant->users()->syncWithoutDetaching([
            $user->id => ['role' => $invitation->role->value],
        ]);

        $invitation->update(['accepted_at' => now()]);

        if ($existing !== null) {
            return redirect()->route('filament.merchant.auth.login')
                ->with('status', __('app.invite_joined_login'));
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/app/'.$invitation->merchant->slug);
    }

    protected function usable(string $token): Invitation
    {
        $invitation = Invitation::findByToken($token);

        abort_if($invitation === null || ! $invitation->isUsable(), 404);

        abort_unless($invitation->merchant->status !== MerchantStatus::Suspended, 403);

        return $invitation;
    }
}
