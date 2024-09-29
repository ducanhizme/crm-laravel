<?php

namespace App\Http\Controllers\Auth;

use App\Enums\TokenAbility;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create($request->validated());
        return $this->successResponse(new UserResource($user), 'User registered successfully', Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request)
    {
        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();
            $accessToken = $user->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinute(config('sanctum.ac_expiration')));
            $refreshToken = $user->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], Carbon::now()->addMinute(config('sanctum.rt_expiration')));
            return $this->successResponse(
                [
                    'user' => new UserResource($user),
                    'access_token' => $accessToken->plainTextToken,
                    'refresh_token' => $refreshToken->plainTextToken,
                ],
                'User logged in successfully');
        }
        return $this->errorResponse('Invalid credentials', Response::HTTP_UNAUTHORIZED);
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

    //Todo : Add method to verify email
}
