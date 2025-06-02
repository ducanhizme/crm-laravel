<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Http\Requests\EventRequest;
use App\Http\Resources\EventResource;
use App\Models\Workspace;
use App\Services\GoogleCalendarService; // Added
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // Added
use Illuminate\Http\Response;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        // Get workspaces the user has access to
        $userWorkspaceIds = $user->workspaces()->pluck('id')->toArray();

        $events = QueryBuilder::for(Event::class)
            ->whereIn('workspace_id', $userWorkspaceIds) // Filter by user's workspaces
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('workspace_id'),
                AllowedFilter::exact('event_type'),
                AllowedFilter::scope('starts_between'), // Assumes you'll add a scope in Event model
                AllowedFilter::scope('ends_between'),   // Assumes you'll add a scope in Event model
            ])
            ->allowedSorts(['start_time', 'end_time', 'title', 'created_at'])
            ->paginate($request->input('per_page', 15));

        return EventResource::collection($events);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EventRequest $request)
    {
        // EventRequest already validated that the workspace_id belongs to the user
        // and merged the authenticated user's ID as user_id.
        $event = Event::create($request->validated());

        // Integrate with Google Calendar
        $user = $request->user();
        if ($user->google_calendar_access_token && $user->google_calendar_refresh_token) {
            try {
                $googleCalendarService = new GoogleCalendarService($user);
                $googleEventId = $googleCalendarService->createEvent($event);
                if ($googleEventId) {
                    $event->google_calendar_event_id = $googleEventId;
                    $event->save();
                }
            } catch (\Exception $e) {
                Log::error('Failed to create Google Calendar event for CRM event ID ' . $event->id . ': ' . $e->getMessage());
                // Don't fail the whole request, just log the error. The CRM event is already created.
            }
        }

        return new EventResource($event);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Event $event)
    {
        $user = $request->user();
        if (!$user->workspaces()->where('id', $event->workspace_id)->exists()) {
            return response()->json(['message' => 'Forbidden: You do not have access to this event.'], Response::HTTP_FORBIDDEN);
        }
        return new EventResource($event);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EventRequest $request, Event $event)
    {
        $user = $request->user();
        // Check if the original event's workspace belongs to the user
        if (!$user->workspaces()->where('id', $event->workspace_id)->exists()) {
            return response()->json(['message' => 'Forbidden: You do not have access to modify this event.'], Response::HTTP_FORBIDDEN);
        }

        // EventRequest validates if new workspace_id (if provided) belongs to the user.
        $validatedData = $request->validated();

        // If workspace_id is being changed, ensure the new workspace also belongs to the user.
        // This is already handled by the 'workspace_id' rule in EventRequest for PUT/PATCH.

        $event->update($validatedData);

        // Integrate with Google Calendar
        $user = $request->user(); // User performing the update
        // We need the event's owner user model to initialize GoogleCalendarService correctly
        $eventOwner = $event->user; // Assumes Event model has a 'user' relationship to its owner/creator

        if ($eventOwner && $eventOwner->google_calendar_access_token && $eventOwner->google_calendar_refresh_token && $event->google_calendar_event_id) {
            try {
                $googleCalendarService = new GoogleCalendarService($eventOwner);
                $googleCalendarService->updateEvent($event);
            } catch (\Exception $e) {
                Log::error('Failed to update Google Calendar event for CRM event ID ' . $event->id . ': ' . $e->getMessage());
                // Don't fail the whole request, just log the error.
            }
        } elseif ($eventOwner && $eventOwner->google_calendar_access_token && $eventOwner->google_calendar_refresh_token && !$event->google_calendar_event_id) {
            // If event was not previously synced, but user is synced, create it now
             try {
                $googleCalendarService = new GoogleCalendarService($eventOwner);
                $googleEventId = $googleCalendarService->createEvent($event);
                if ($googleEventId) {
                    $event->google_calendar_event_id = $googleEventId;
                    $event->save(); // Save again to store the new google_calendar_event_id
                }
            } catch (\Exception $e) {
                Log::error('Failed to create Google Calendar event during update for CRM event ID ' . $event->id . ': ' . $e->getMessage());
            }
        }


        return new EventResource($event->fresh()); // Use fresh() to get potentially updated google_calendar_event_id
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Event $event)
    {
        $user = $request->user();
        if (!$user->workspaces()->where('id', $event->workspace_id)->exists()) {
            return response()->json(['message' => 'Forbidden: You do not have access to delete this event.'], Response::HTTP_FORBIDDEN);
        }

        // Integrate with Google Calendar
        $eventOwner = $event->user; // Assumes Event model has a 'user' relationship

        if ($eventOwner && $eventOwner->google_calendar_access_token && $eventOwner->google_calendar_refresh_token && $event->google_calendar_event_id) {
            try {
                $googleCalendarService = new GoogleCalendarService($eventOwner);
                $googleCalendarService->deleteEvent($event->google_calendar_event_id);
            } catch (\Exception $e) {
                Log::error('Failed to delete Google Calendar event for CRM event ID ' . $event->id . ': ' . $e->getMessage());
                // Don't fail the whole request, just log the error.
                // Proceed with deleting the CRM event.
            }
        }

        $event->delete();
        return response()->noContent();
    }
}
