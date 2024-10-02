<?php

namespace App\Http\Controllers;

use App\Http\Requests\StatusRequest;
use App\Http\Resources\StatusResource;
use App\Models\Status;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class StatusController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Status::class);

        return StatusResource::collection(Status::all());
    }

    public function store(StatusRequest $request)
    {
        $this->authorize('create', Status::class);

        return new StatusResource(Status::create($request->validated()));
    }

    public function show(Status $status)
    {
        $this->authorize('view', $status);

        return new StatusResource($status);
    }

    public function update(StatusRequest $request, Status $status)
    {
        $this->authorize('update', $status);

        $status->update($request->validated());

        return new StatusResource($status);
    }

    public function destroy(Status $status)
    {
        $this->authorize('delete', $status);

        $status->delete();

        return response()->json();
    }
}
