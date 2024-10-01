<?php

namespace App\Http\Middleware;

use App\Models\Invitation;
use App\Traits\HasApiResponse;
use Closure;
use http\Client\Curl\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ValidateInvitationToken
{
    use HasApiResponse;

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token');
        $invitation = Invitation::where('token', $token)->first();
        if (!$invitation || $invitation->isExpired()) {
            return response()->json(['message' => 'Invalid or expired invitation token'], 404);
        }
        $user = \App\Models\User::where('email', $invitation->email)->first();
        if ($user && $user->id == \Auth::id()) {
            if ($user->hasJoinedWorkspace($invitation->workspace_id)) {
                return $this->errorResponse('You have already joined this workspace', Response::HTTP_BAD_REQUEST);
            }
        } else {
            return $this->errorResponse('You are not authorized to join this workspace', Response::HTTP_UNAUTHORIZED);
        }
        $request->merge(['invitation' => $invitation]);
        return $next($request);
    }
}
