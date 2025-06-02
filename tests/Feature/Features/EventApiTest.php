<?php

namespace Tests\Feature\Features;

use App\Models\Event;
use App\Models\User;
use App\Models\Workspace;
use App\Services\GoogleCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Mockery;
use Mockery\MockInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Attributes\Test; // Import the Test attribute


class EventApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Workspace $workspace;
    protected MockInterface $googleCalendarServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['created_by' => $this->user->id]);
        $this->user->joinedWorkspace()->attach($this->workspace);
        // $this->user->current_workspace_id = $this->workspace->id; // This column does not exist on users table
        // $this->user->save(); // Removed as current_workspace_id is not persisted on users table

        // If CurrentWorkspaceMiddleware relies on user->current_workspace_id, ensure it's set on the model instance for the test duration
        $this->user->current_workspace_id = $this->workspace->id;

        Sanctum::actingAs($this->user, ['access-api']); // Default to authenticated user

        // Mock GoogleCalendarService
        $this->googleCalendarServiceMock = Mockery::mock(GoogleCalendarService::class);
        $this->app->instance(GoogleCalendarService::class, $this->googleCalendarServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // --- Event Creation (POST /events) ---

    #[Test]
    public function can_create_event_with_valid_data()
    {
        $this->googleCalendarServiceMock->shouldReceive('createEvent')->nullable()->andReturn('mock_google_event_id');

        $eventData = [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'event_type' => 'meeting',
            'start_time' => Carbon::now()->addDay()->toDateTimeString(),
            'end_time' => Carbon::now()->addDay()->addHours(2)->toDateTimeString(),
            'workspace_id' => $this->workspace->id,
            // user_id should be set automatically by EventRequest
        ];

        $response = $this->postJson(route('events.store'), $eventData);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'title', 'description', 'event_type', 'start_time', 'end_time', 'workspace_id', 'user_id'])
            ->assertJsonFragment(['title' => $eventData['title'], 'workspace_id' => $this->workspace->id, 'user_id' => $this->user->id]);

        $this->assertDatabaseHas('events', ['title' => $eventData['title'], 'user_id' => $this->user->id]);
    }

    #[Test]
    public function cannot_create_event_with_invalid_data()
    {
        $response = $this->postJson(route('events.store'), ['title' => '']); // Missing other required fields
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'event_type', 'start_time', 'end_time', 'workspace_id']);
    }

    #[Test]
    public function unauthenticated_user_cannot_create_event()
    {
        Auth::logout(); // No, this is for web. For Sanctum, just don't call actingAs or use a new client.
        $client = $this->createClient(); // Create a new unauthenticated client instance
        $response = $client->postJson(route('events.store'), []);
        $response->assertStatus(401);
    }

    // Helper method, no #[Test]
    protected function createClient()
    {
        // A bit of a hack to get an unauthenticated client if needed,
        // as $this->app will keep the authenticated user from Sanctum::actingAs
        $newApp = require base_path('bootstrap/app.php');
        $newApp->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return new \Illuminate\Testing\TestResponse($newApp->make('request')->create(
            route('events.store'), 'POST'
        )->server->add(['HTTP_ACCEPT' => 'application/json']));
    }


    #[Test]
    public function user_cannot_create_event_in_workspace_they_dont_belong_to()
    {
        $otherWorkspace = Workspace::factory()->create(); // User does not belong to this

        $eventData = [
            'title' => 'Intruder Event',
            'event_type' => 'meeting',
            'start_time' => Carbon::now()->addDay()->toDateTimeString(),
            'end_time' => Carbon::now()->addDay()->addHours(2)->toDateTimeString(),
            'workspace_id' => $otherWorkspace->id,
        ];

        $response = $this->postJson(route('events.store'), $eventData);
        $response->assertStatus(422); // EventRequest validation for workspace_id should fail
        $response->assertJsonValidationErrors(['workspace_id']);
    }


    // --- Event Listing (GET /events) ---
    #[Test]
    public function can_list_events_for_own_workspaces()
    {
        Event::factory()->count(3)->create(['workspace_id' => $this->workspace->id, 'user_id' => $this->user->id]);
        $otherWorkspace = Workspace::factory()->create(); // User does not belong
        Event::factory()->count(2)->create(['workspace_id' => $otherWorkspace->id]);

        $response = $this->getJson(route('events.index'));
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data'); // Should only see events from $this->workspace
    }

    #[Test]
    public function can_filter_events_by_type_and_time()
    {
        Event::factory()->create(['workspace_id' => $this->workspace->id, 'user_id' => $this->user->id, 'event_type' => 'meeting', 'start_time' => Carbon::now()->addHours(1)]);
        Event::factory()->create(['workspace_id' => $this->workspace->id, 'user_id' => $this->user->id, 'event_type' => 'task', 'start_time' => Carbon::now()->addHours(2)]);
        Event::factory()->create(['workspace_id' => $this->workspace->id, 'user_id' => $this->user->id, 'event_type' => 'meeting', 'start_time' => Carbon::now()->addHours(3)]);

        $response = $this->getJson(route('events.index', ['filter[event_type]' => 'meeting']));
        $response->assertStatus(200)->assertJsonCount(2, 'data');

        $startTime = Carbon::now()->addMinutes(50)->toDateTimeString();
        $endTime = Carbon::now()->addMinutes(70)->toDateTimeString();
        // Note: The scope filter syntax might be filter[starts_between]=datetime1,datetime2
        $response = $this->getJson(route('events.index', ['filter[starts_between]' => $startTime.','.$endTime]));
        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    // --- Show Event (GET /events/{id}) ---
    #[Test]
    public function can_show_own_event()
    {
        $event = Event::factory()->create(['workspace_id' => $this->workspace->id, 'user_id' => $this->user->id]);
        $response = $this->getJson(route('events.show', $event));
        $response->assertStatus(200)->assertJsonFragment(['id' => $event->id]);
    }

    #[Test]
    public function cannot_show_event_from_unrelated_workspace()
    {
        $otherUser = User::factory()->create();
        $otherWorkspace = Workspace::factory()->create(['created_by' => $otherUser->id]);
        $otherUser->joinedWorkspace()->attach($otherWorkspace);
        $eventInOtherWorkspace = Event::factory()->create(['workspace_id' => $otherWorkspace->id, 'user_id' => $otherUser->id]);

        $response = $this->getJson(route('events.show', $eventInOtherWorkspace));
        $response->assertStatus(403); // Forbidden
    }

    #[Test]
    public function show_event_returns_404_for_invalid_id()
    {
        $response = $this->getJson(route('events.show', 999));
        $response->assertStatus(404);
    }


    // --- Update Event (PUT /events/{id}) ---
    #[Test]
    public function can_update_own_event()
    {
        $event = Event::factory()->create(['workspace_id' => $this->workspace->id, 'user_id' => $this->user->id]);
        $updateData = ['title' => 'Updated Event Title'];

        $this->googleCalendarServiceMock->shouldReceive('updateEvent')->nullable(); // Or createEvent if not synced

        $response = $this->putJson(route('events.update', $event), $updateData);
        $response->assertStatus(200)->assertJsonFragment(['title' => 'Updated Event Title']);
        $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'Updated Event Title']);
    }

    #[Test]
    public function cannot_update_event_in_unrelated_workspace()
    {
        $otherUser = User::factory()->create();
        $otherWorkspace = Workspace::factory()->create(['created_by' => $otherUser->id]);
        $otherUser->joinedWorkspace()->attach($otherWorkspace);
        $eventInOtherWorkspace = Event::factory()->create(['workspace_id' => $otherWorkspace->id, 'user_id' => $otherUser->id]);

        $response = $this->putJson(route('events.update', $eventInOtherWorkspace), ['title' => 'Attempted Update']);
        $response->assertStatus(403);
    }


    // --- Delete Event (DELETE /events/{id}) ---
    #[Test]
    public function can_delete_own_event()
    {
        $event = Event::factory()->create(['workspace_id' => $this->workspace->id, 'user_id' => $this->user->id, 'google_calendar_event_id' => 'test_gcal_id']);
        $this->googleCalendarServiceMock->shouldReceive('deleteEvent')->with('test_gcal_id')->once()->andReturn(true);

        $response = $this->deleteJson(route('events.destroy', $event));
        $response->assertStatus(204); // No Content
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    #[Test]
    public function cannot_delete_event_in_unrelated_workspace()
    {
        $otherUser = User::factory()->create();
        $otherWorkspace = Workspace::factory()->create(['created_by' => $otherUser->id]);
        $otherUser->joinedWorkspace()->attach($otherWorkspace);
        $eventInOtherWorkspace = Event::factory()->create(['workspace_id' => $otherWorkspace->id, 'user_id' => $otherUser->id]);

        $response = $this->deleteJson(route('events.destroy', $eventInOtherWorkspace));
        $response->assertStatus(403);
    }

    // --- Google Calendar Sync Logic Tests ---

    #[Test]
    public function event_creation_calls_googlecalendarservice_if_user_is_synced()
    {
        // User already has tokens from setUp, make them valid for this test
        $this->user->update([
            'google_calendar_access_token' => Crypt::encryptString('valid_token'),
            'google_calendar_refresh_token' => Crypt::encryptString('valid_refresh'),
        ]);
        Sanctum::actingAs($this->user, ['access-api']); // Re-authenticate with updated user

        $this->googleCalendarServiceMock
            ->shouldReceive('createEvent')
            ->once()
            ->andReturn('mock_google_event_id');

        $eventData = Event::factory()->make(['workspace_id' => $this->workspace->id, 'user_id' => $this->user->id])->toArray();
        // Ensure dates are strings for JSON request
        $eventData['start_time'] = Carbon::parse($eventData['start_time'])->toDateTimeString();
        $eventData['end_time'] = Carbon::parse($eventData['end_time'])->toDateTimeString();


        $response = $this->postJson(route('events.store'), $eventData);
        $response->assertStatus(201);
        $this->assertDatabaseHas('events', ['id' => $response->json('id'), 'google_calendar_event_id' => 'mock_google_event_id']);
    }

    #[Test]
    public function event_creation_does_not_call_googlecalendarservice_if_user_is_not_synced()
    {
        $userWithoutTokens = User::factory()->create();
        $userWithoutTokens->joinedWorkspace()->attach($this->workspace); // Attach to the same workspace for the test
        Sanctum::actingAs($userWithoutTokens, ['access-api']);

        $this->googleCalendarServiceMock->shouldNotReceive('createEvent');

        $eventData = Event::factory()->make(['workspace_id' => $this->workspace->id, 'user_id' => $userWithoutTokens->id])->toArray();
        $eventData['start_time'] = Carbon::parse($eventData['start_time'])->toDateTimeString();
        $eventData['end_time'] = Carbon::parse($eventData['end_time'])->toDateTimeString();


        $response = $this->postJson(route('events.store'), $eventData);
        $response->assertStatus(201);
        $this->assertDatabaseHas('events', ['id' => $response->json('id'), 'google_calendar_event_id' => null]);
    }

    #[Test]
    public function event_update_calls_googlecalendarservice_updateevent_if_synced_and_has_google_id()
    {
        $this->user->update([
            'google_calendar_access_token' => Crypt::encryptString('valid_token'),
            'google_calendar_refresh_token' => Crypt::encryptString('valid_refresh'),
        ]);
        Sanctum::actingAs($this->user, ['access-api']);

        $event = Event::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'google_calendar_event_id' => 'existing_google_id'
        ]);

        $this->googleCalendarServiceMock->shouldReceive('updateEvent')->once()->andReturn(Mockery::mock(Google_Service_Calendar_Event::class));

        $this->putJson(route('events.update', $event), ['title' => 'Updated Title Again']);
    }

    #[Test]
    public function event_update_calls_googlecalendarservice_createevent_if_synced_but_no_google_id()
    {
        $this->user->update([
            'google_calendar_access_token' => Crypt::encryptString('valid_token'),
            'google_calendar_refresh_token' => Crypt::encryptString('valid_refresh'),
        ]);
        Sanctum::actingAs($this->user, ['access-api']);

        $event = Event::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'google_calendar_event_id' => null // Not synced yet
        ]);

        $this->googleCalendarServiceMock->shouldReceive('createEvent')->once()->andReturn('new_google_id');

        $response = $this->putJson(route('events.update', $event), ['title' => 'Sync This Event Now']);
        $response->assertStatus(200);
        $this->assertDatabaseHas('events', ['id' => $event->id, 'google_calendar_event_id' => 'new_google_id']);
    }


    #[Test]
    public function event_deletion_calls_googlecalendarservice_deleteevent_if_synced()
    {
         $this->user->update([
            'google_calendar_access_token' => Crypt::encryptString('valid_token'),
            'google_calendar_refresh_token' => Crypt::encryptString('valid_refresh'),
        ]);
        Sanctum::actingAs($this->user, ['access-api']);

        $event = Event::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'google_calendar_event_id' => 'id_to_delete_on_google'
        ]);

        $this->googleCalendarServiceMock->shouldReceive('deleteEvent')->with('id_to_delete_on_google')->once()->andReturn(true);

        $this->deleteJson(route('events.destroy', $event));
    }
}
