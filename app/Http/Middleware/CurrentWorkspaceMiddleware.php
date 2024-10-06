<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use App\Traits\HasApiResponse;
use Closure;
use Illuminate\Http\Request;

class CurrentWorkspaceMiddleware
{
    use HasApiResponse;
    public function handle(Request $request, Closure $next)
    {
        $currentWorkspaceId = $request->header('x-current-workspace-id');
        if ($currentWorkspace = Workspace::where('id', $currentWorkspaceId)->get()->first()) {
            $request->merge(['current_workspace' => $currentWorkspace]);
            return $next($request);
        }else{
            return $this->errorResponse('Workspace has id '.$currentWorkspaceId.' not found', 404);
        }
    }
}
