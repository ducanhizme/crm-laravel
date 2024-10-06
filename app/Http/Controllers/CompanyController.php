<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $currentWorkspace =$request->current_workspace;
        $this->authorize('viewAny', [Company::class,$currentWorkspace]);
        return CompanyResource::collection(Company::all());
    }

    public function store(CompanyRequest $request)
    {
        $currentWorkspace =$request->current_workspace;
        $this->authorize('create', Company::class);
        $company = $currentWorkspace->companies()->create($request->validated()+['created_by'=>auth()->id()]);
        return $this->respondCreated(new CompanyResource($company), 'Company created successfully');
    }

    public function show(Request $request,Company $company)
    {
        $currentWorkspace =$request->current_workspace;
        $this->authorize('view', [$company,$currentWorkspace]);
        return $this->respondWithSuccess(new CompanyResource($company),'Company retrieved successfully');
    }

    public function update(CompanyRequest $request, Company $company)
    {
        $currentWorkspace =$request->current_workspace;
        $this->authorize('update', [$company,$currentWorkspace]);
        $company->update($request->validated());
        return $this->respondWithSuccess(new CompanyResource($company),'Company updated successfully');
    }

    public function destroy(Request $request,Company $company)
    {
        $currentWorkspace =$request->current_workspace;
        $this->authorize('delete', [$company,$currentWorkspace]);
        $company->delete();
        return response()->json();
    }
}
