<?php

namespace App\Http\Controllers\Auth;

use App\Enums\TokenAbility;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\HasApiResponse;
use Carbon\Carbon;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    use HasApiResponse;
    public function redirect(){
       $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
       return $this->respondWithSuccess(['url' => $url], 'Google login redirect url generated successfully');
    }

    public function callback(){
        try {
        $googleUser = Socialite::driver('google')->stateless()->user();
        $user = User::updateOrCreate(
            ['google_id' => $googleUser->getId()],
            [
                'first_name' => $googleUser->getFamilyName(),
                'last_name' => $googleUser->getGivenName(),
                'email' => $googleUser->getEmail(),
                'password' => bcrypt('password'),
            ]
        );
        $user->markEmailAsVerified();
        \Auth::login($user);
        $accessToken = $user->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinute(config('sanctum.ac_expiration')));
        $refreshToken = $user->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], Carbon::now()->addMinute(config('sanctum.rt_expiration')));
        return $this->respondWithSuccess(
            [
                'user' => $user,
                'access_token' => $accessToken->plainTextToken,
                'refresh_token' => $refreshToken->plainTextToken,
            ],
            'Google user logged in successfully'
        );
        } catch (\Exception $e) {
            return $this->respondError('An error occurred during Google login'. $e->getMessage());
        }
    }
}
