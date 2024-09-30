<?php
namespace App\Http\Controllers\Auth;
use App\Enums\TokenAbility;
use App\Events\RegisterEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create($request->validated());
        event(new RegisterEvent($user));
        return $this->successResponse(new UserResource($user), 'User registered successfully. Please verify your email that you provided', Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse('Invalid credentials', Response::HTTP_UNAUTHORIZED);
        }
        $user = Auth::user();
        $accessToken = $user->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinute(config('sanctum.ac_expiration')));
        $refreshToken = $user->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], Carbon::now()->addMinute(config('sanctum.rt_expiration')));
        return $this->successResponse(
            [
                'verified' => $user->hasVerifiedEmail(),
                'user' => new UserResource($user),
                'access_token' => $accessToken->plainTextToken,
                'refresh_token' => $refreshToken->plainTextToken,
            ],
            'User logged in successfully'
        );
    }

    public function logout()
    {
        Auth::user()->tokens()->delete();
        return $this->successResponse([], 'User logged out successfully');
    }

    public function refreshToken()
    {
        $token = Auth::user()->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinute(config('sanctum.ac_expiration')));
        return $this->successResponse(['access_token' => $token->plainTextToken], 'Token refreshed successfully');
    }

    public function verifyEmail(EmailVerificationRequest $request)
    {
        $request->fulfill();
        return $this->successResponse([], 'Email verified successfully');
    }

    public function resendVerificationEmail()
    {
        Auth::user()->sendEmailVerificationNotification();
        return $this->successResponse([], 'Verification email sent successfully');
    }

}
