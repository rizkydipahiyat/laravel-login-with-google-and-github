<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProvideCallback($provider)
    {
        try {
            $user = Socialite::driver($provider)->stateless()->user();
        } catch (Exception $e) {
            return redirect()->back();
        }
        // find or create user and send params user get from socialite and provider
        $authUser = $this->findOrCreateUser($user, $provider);

        // login user
        Auth()->login($authUser, true);

        // login success redirect to dashboard
        return redirect()->route('dashboard');
    }

    public function findOrCreateUser($socialUser, $provider)
    {
        $socialAccount = SocialAccount::where('provider_id', $socialUser->getId())->where('provider_name', $provider)->first();

        // Jika User Ada
        if ($socialAccount) {
            return $socialAccount->user;
        } else {
            // User Berdasarkan Email
            $user = User::where('email', $socialUser->getEmail())->first();

            // Jika Tidak Ada User
            if (!$user) {
                // Create New User
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail()
                ]);
            }
            // Create New  Social Account
            $user->socialAccounts()->create([
                'provider_id'   => $socialUser->getId(),
                'provider_name' => $provider
            ]);
            return $user;
        }
    }
}
