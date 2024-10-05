<?php

namespace App\Http\Controllers\Workspace;

use App\Events\InvitationSentEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\InvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;

class InvitationController extends Controller
{
    public function invite(InvitationRequest $request)
    {
        $invitation = User::inviteToWorkspace($request);
        return $this->successResponse(new InvitationResource($invitation), 'Invitation sent successfully');
    }

    public function accept($token)
    {
        $invitation = \Request::get('invitation');
        $invitation->workspace->users()->attach(\Auth::id());
        Invitation::where('email', $invitation->email)->delete();
        return $this->successResponse([], 'Invitation accepted successfully');
    }
}
