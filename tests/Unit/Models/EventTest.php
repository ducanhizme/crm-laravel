<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Auth; // Added for Auth::login

class EventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function event_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $user->id]);
        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals($user->id, $event->user->id);
    }

    #[Test]
    public function event_belongs_to_a_workspace()
    {
        $workspace = Workspace::factory()->create();
        $event = Event::factory()->create(['workspace_id' => $workspace->id]);
        $this->assertInstanceOf(Workspace::class, $event->workspace);
        $this->assertEquals($workspace->id, $event->workspace->id);
    }

    #[Test]
    public function scope_starts_between_filters_events_correctly()
    {
        $user = User::factory()->create();
        // Ensure workspace is created by this user for consistency if policies apply
        $workspace = Workspace::factory()->create(['created_by' => $user->id]);
        $user->joinedWorkspace()->attach($workspace); // Attach user to workspace
        Auth::login($user); // Login the user for any auth checks

        // Mock current workspace in request if necessary for your application context
        // This setup was in your original test, keeping it for context,
        // though for a pure model scope test, it might not always be strictly necessary
        // unless other global scopes depend on it.
        $request = new \Illuminate\Http\Request();
        $request->merge(['current_workspace' => $workspace]); // Simulate current workspace context
        $request->setUserResolver(function () use ($user) { return $user; }); // Set the user for the request
        $this->app->instance('request', $request); // Bind to service container


        $event1 = Event::factory()->create([
            'start_time' => Carbon::now()->addDay(),
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
        ]);
        $event2 = Event::factory()->create([
            'start_time' => Carbon::now()->addDays(3),
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
        ]);
        Event::factory()->create([ // event3 (starts before range)
            'start_time' => Carbon::now()->subDay(),
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
        ]);

        $startDate = Carbon::now()->startOfDay();
        $endDate = Carbon::now()->addDays(2)->endOfDay();

        $filteredEvents = Event::startsBetween([$startDate->toDateTimeString(), $endDate->toDateTimeString()])->get();

        $this->assertCount(1, $filteredEvents);
        $this->assertTrue($filteredEvents->contains($event1));
        $this->assertFalse($filteredEvents->contains($event2));


        $event4 = Event::factory()->create([
            'start_time' => Carbon::create(2024, 1, 15, 10, 0, 0),
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
        ]);
        Event::factory()->create([ // event5 (starts outside this specific range)
            'start_time' => Carbon::create(2024, 1, 17, 10, 0, 0),
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
        ]);

        $filtered = Event::startsBetween([
            Carbon::create(2024, 1, 15, 0, 0, 0)->toDateTimeString(),
            Carbon::create(2024, 1, 16, 23, 59, 59)->toDateTimeString()
        ])->get();
        $this->assertCount(1, $filtered);
        $this->assertTrue($filtered->contains($event4));
    }

    #[Test]
    public function scope_ends_between_filters_events_correctly()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['created_by' => $user->id]);
        $user->joinedWorkspace()->attach($workspace);
        Auth::login($user);

        $request = new \Illuminate\Http\Request();
        $request->merge(['current_workspace' => $workspace]);
        $request->setUserResolver(function () use ($user) { return $user; });
        $this->app->instance('request', $request);

        $event1 = Event::factory()->create([
            'end_time' => Carbon::now()->addDay(), // Ends within range
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
        ]);
        $event2 = Event::factory()->create([
            'end_time' => Carbon::now()->addDays(3), // Ends outside range
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
        ]);
        Event::factory()->create([ // event3 (ends before range)
            'end_time' => Carbon::now()->subDay(),
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
        ]);

        $startDate = Carbon::now()->startOfDay();
        $endDate = Carbon::now()->addDays(2)->endOfDay();

        $filteredEvents = Event::endsBetween([$startDate->toDateTimeString(), $endDate->toDateTimeString()])->get();

        $this->assertCount(1, $filteredEvents);
        $this->assertTrue($filteredEvents->contains($event1));
        $this->assertFalse($filteredEvents->contains($event2));
    }
}
