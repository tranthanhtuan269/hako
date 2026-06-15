<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AffiliateService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    public function register(Request $request, AffiliateService $affiliate): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_admin' => false,
        ]);

        $referralCode = $request->cookie(config('affiliate.cookie_name', 'affiliate_ref'));
        if (config('affiliate.enabled')) {
            $affiliate->attachReferrerToNewUser($user, $referralCode);
        }

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('member.dashboard')->with('success', 'Welcome! Your account has been created.');
    }
}
