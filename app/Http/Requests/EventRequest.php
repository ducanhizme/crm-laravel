<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Workspace; // Added for checking workspace existence

class EventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Basic authorization: allow if the user is authenticated.
        // More specific authorization (e.g., user belongs to the workspace)
        // will be handled in the controller or via a policy.
        return $this->user() != null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|string|max:100', // e.g., 'meeting', 'task', 'reminder'
            'start_time' => 'required|date',
            'end_time' => 'required|date|after_or_equal:start_time',
            'google_calendar_event_id' => 'nullable|string|max:255',
        ];

        // For store, workspace_id is required and must exist and belong to the user.
        // For update, workspace_id is optional, but if provided, must exist and belong to the user.
        if ($this->isMethod('post')) { // Creating a new event
            $rules['workspace_id'] = [
                'required',
                'integer',
                Rule::exists(Workspace::class, 'id')->where(function ($query) {
                    // Ensure the workspace belongs to the authenticated user
                    $query->whereIn('id', $this->user()->workspaces()->pluck('id')->toArray());
                }),
            ];
        } else if ($this->isMethod('put') || $this->isMethod('patch')) { // Updating an event
            $rules['workspace_id'] = [
                'sometimes', // Make it optional during update
                'integer',
                Rule::exists(Workspace::class, 'id')->where(function ($query) {
                    // Ensure the workspace belongs to the authenticated user
                    $query->whereIn('id', $this->user()->workspaces()->pluck('id')->toArray());
                }),
            ];
        }

        return $rules;
    }

    /**
     * Prepare the data for validation.
     *
     * This method is called before the validation rules are applied.
     * We can use it to merge the authenticated user's ID into the request data.
     */
    protected function prepareForValidation(): void
    {
        // Automatically set user_id from the authenticated user for new events.
        // For updates, user_id should generally not change, but if it's part of the request,
        // it should match the authenticated user or be handled by specific authorization logic.
        // Here, we ensure user_id is always set to the authenticated user's ID if not present
        // or if it's a POST request.
        if ($this->isMethod('post')) {
            $this->merge([
                'user_id' => $this->user()->id,
            ]);
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'workspace_id.exists' => 'The selected workspace is invalid or you do not have access to it.',
        ];
    }
}
