<?php

namespace Tests\Unit\Services;

use App\Models\Event as CrmEvent;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_Events; // Needed for mocking events->insert etc.
use Google_Service_Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Illuminate\Encryption\Encrypter;
use PHPUnit\Framework\Attributes\Test; // Import the Test attribute

class GoogleCalendarServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected MockInterface $googleClientMock;
    protected MockInterface $googleServiceCalendarMock;
    protected MockInterface $googleServiceCalendarEventsMock; // Mock for the 'events' resource

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure APP_KEY and APP_CIPHER are explicitly set for testing encryption.
        // Use $this->app['config']->set() for more direct impact in test lifecycle.
        $cipher = 'AES-256-CBC'; // A standard Laravel cipher
        $this->app['config']->set('app.cipher', $cipher);
        $this->app['config']->set('app.key', 'base64:'.Encrypter::generateKey($cipher));


        $this->user = User::factory()->create([
            'google_calendar_access_token' => Crypt::encryptString('test_access_token'),
            'google_calendar_refresh_token' => Crypt::encryptString('test_refresh_token'),
            'google_calendar_token_expires_at' => Carbon::now()->addHour(),
        ]);

        $this->googleClientMock = Mockery::mock(Google_Client::class);
        $this->googleServiceCalendarMock = Mockery::mock(Google_Service_Calendar::class);
        $this->googleServiceCalendarEventsMock = Mockery::mock(Google_Service_Calendar_Events::class); // Mock the 'events' property

        // Configure the service calendar mock to return the events mock
        $this->googleServiceCalendarMock->events = $this->googleServiceCalendarEventsMock;

        // Mock Google_Client methods used in constructor and refreshToken
        $this->googleClientMock->shouldReceive('setClientId')->zeroOrMoreTimes();
        $this->googleClientMock->shouldReceive('setClientSecret')->zeroOrMoreTimes();
        $this->googleClientMock->shouldReceive('setRedirectUri')->zeroOrMoreTimes();
        $this->googleClientMock->shouldReceive('setAccessToken')->zeroOrMoreTimes();
        $this->googleClientMock->shouldReceive('isAccessTokenExpired')->zeroOrMoreTimes()->andReturn(false); // Default to not expired
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function getServiceInstance(User $user = null): GoogleCalendarService
    {
        $userToUse = $user ?: $this->user;
        // This replaces the actual new Google_Client and new Google_Service_Calendar in the constructor
        return $this->app->instance(GoogleCalendarService::class, new GoogleCalendarService($userToUse));
    }

    #[Test]
    public function it_can_be_instantiated_with_a_valid_user()
    {
        $this->overrideGoogleClientCreation();
        $service = new GoogleCalendarService($this->user);
        $this->assertInstanceOf(GoogleCalendarService::class, $service);
    }

    #[Test]
    public function it_throws_exception_if_user_has_no_access_token()
    {
        $this->overrideGoogleClientCreation();
        $userWithoutToken = User::factory()->create(['google_calendar_access_token' => null]);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User has not authenticated with Google Calendar.');
        new GoogleCalendarService($userWithoutToken);
    }

    // This is a helper method, not a test, so no #[Test] attribute
    protected function overrideGoogleClientCreation(bool $tokenExpired = false, bool $refreshTokenFails = false, bool $noRefreshToken = false)
    {
        $this->googleClientMock = Mockery::mock(Google_Client::class);
        $this->googleClientMock->shouldReceive('setClientId')->once();
        $this->googleClientMock->shouldReceive('setClientSecret')->once();
        $this->googleClientMock->shouldReceive('setRedirectUri')->once();
        $this->googleClientMock->shouldReceive('setAccessToken')->once();

        if ($tokenExpired) {
            $this->googleClientMock->shouldReceive('isAccessTokenExpired')->andReturn(true); // Mock as expired
             if ($noRefreshToken) {
                // No further mocking needed for client if refresh token itself is missing in DB
            } elseif ($refreshTokenFails) {
                $this->googleClientMock->shouldReceive('fetchAccessTokenWithRefreshToken')
                                     ->once()
                                     ->andThrow(new \Exception('Failed to fetch'));
            } else {
                $this->googleClientMock->shouldReceive('fetchAccessTokenWithRefreshToken')
                                     ->once()
                                     ->andReturn(['access_token' => 'new_access_token', 'expires_in' => 3600]);
                $this->googleClientMock->shouldReceive('getAccessToken')->andReturn('new_access_token'); // After refresh
                // This setAccessToken is for the *new* token after refresh
                $this->googleClientMock->shouldReceive('setAccessToken')->once()->with('new_access_token');
            }
        } else {
            $this->googleClientMock->shouldReceive('isAccessTokenExpired')->andReturn(false);
        }


        // Replace the actual Google_Client instantiation in the service
        $this->app->when(GoogleCalendarService::class)
            ->needs(Google_Client::class)
            ->give(fn () => $this->googleClientMock);
    }


    #[Test]
    public function create_event_inserts_event_to_google_calendar_and_returns_id()
    {
        $this->overrideGoogleClientCreation();
        $service = new GoogleCalendarService($this->user); // Re-init with overridden client

        $crmEvent = CrmEvent::factory()->make([ // make() doesn't save to DB, useful if not needed
            'title' => 'Test Event',
            'description' => 'Test Description',
            'start_time' => Carbon::now()->addHour(),
            'end_time' => Carbon::now()->addHours(2),
        ]);

        $googleCalendarEventMock = Mockery::mock(Google_Service_Calendar_Event::class);
        $googleCalendarEventMock->shouldReceive('getId')->andReturn('google_event_id_123');

        // Ensure the 'events' resource is properly mocked on the service instance's calendar service
        $serviceCalendar = new Google_Service_Calendar($this->googleClientMock); // Service needs a client
        $eventsResourceMock = Mockery::mock(Google_Service_Calendar_Events::class);
        $eventsResourceMock->shouldReceive('insert')
            ->once()
            ->with('primary', Mockery::on(function ($arg) use ($crmEvent) {
                return $arg instanceof Google_Service_Calendar_Event &&
                       $arg->getSummary() === $crmEvent->title &&
                       $arg->getDescription() === $crmEvent->description;
            }))
            ->andReturn($googleCalendarEventMock);
        $serviceCalendar->events = $eventsResourceMock;

        // Manually inject the mocked Google_Service_Calendar into the service
        $reflection = new \ReflectionClass($service);
        $serviceProperty = $reflection->getProperty('service');
        $serviceProperty->setAccessible(true);
        $serviceProperty->setValue($service, $serviceCalendar);


        $googleEventId = $service->createEvent($crmEvent);

        $this->assertEquals('google_event_id_123', $googleEventId);
    }

    #[Test]
    public function create_event_handles_google_api_exception()
    {
        $this->overrideGoogleClientCreation();
        Log::shouldReceive('error')->once();
        $service = new GoogleCalendarService($this->user);


        $crmEvent = CrmEvent::factory()->make();

        $serviceCalendar = new Google_Service_Calendar($this->googleClientMock);
        $eventsResourceMock = Mockery::mock(Google_Service_Calendar_Events::class);
        $eventsResourceMock->shouldReceive('insert')
            ->once()
            ->andThrow(new \Google\Service\Exception("API error"));
        $serviceCalendar->events = $eventsResourceMock;

        $reflection = new \ReflectionClass($service);
        $serviceProperty = $reflection->getProperty('service');
        $serviceProperty->setAccessible(true);
        $serviceProperty->setValue($service, $serviceCalendar);

        $googleEventId = $service->createEvent($crmEvent);

        $this->assertNull($googleEventId);
    }


    #[Test]
    public function update_event_updates_google_calendar_event()
    {
        $this->overrideGoogleClientCreation();
        $service = new GoogleCalendarService($this->user);

        $crmEvent = CrmEvent::factory()->make([
            'google_calendar_event_id' => 'existing_google_event_id',
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'start_time' => Carbon::now()->addHour(),
            'end_time' => Carbon::now()->addHours(2),
        ]);

        $googleCalendarEventMock = Mockery::mock(Google_Service_Calendar_Event::class);
        $googleCalendarEventMock->shouldReceive('getId')->andReturn('existing_google_event_id');
        $googleCalendarEventMock->shouldReceive('setSummary')->with($crmEvent->title)->once();
        $googleCalendarEventMock->shouldReceive('setDescription')->with($crmEvent->description)->once();
        $googleCalendarEventMock->shouldReceive('setStart')->once();
        $googleCalendarEventMock->shouldReceive('setEnd')->once();

        $serviceCalendar = new Google_Service_Calendar($this->googleClientMock);
        $eventsResourceMock = Mockery::mock(Google_Service_Calendar_Events::class);
        $eventsResourceMock->shouldReceive('get')
            ->with('primary', 'existing_google_event_id')
            ->once()
            ->andReturn($googleCalendarEventMock);
        $eventsResourceMock->shouldReceive('update')
            ->with('primary', 'existing_google_event_id', $googleCalendarEventMock)
            ->once()
            ->andReturn($googleCalendarEventMock);
        $serviceCalendar->events = $eventsResourceMock;

        $reflection = new \ReflectionClass($service);
        $serviceProperty = $reflection->getProperty('service');
        $serviceProperty->setAccessible(true);
        $serviceProperty->setValue($service, $serviceCalendar);

        $updatedEvent = $service->updateEvent($crmEvent);
        $this->assertInstanceOf(Google_Service_Calendar_Event::class, $updatedEvent);
    }

    #[Test]
    public function update_event_returns_null_if_crm_event_has_no_google_id()
    {
        $this->overrideGoogleClientCreation();
        Log::shouldReceive('warning')->once();
        $service = new GoogleCalendarService($this->user);
        $crmEvent = CrmEvent::factory()->make(['google_calendar_event_id' => null]);

        $result = $service->updateEvent($crmEvent);
        $this->assertNull($result);
    }


    #[Test]
    public function delete_event_deletes_from_google_calendar()
    {
        $this->overrideGoogleClientCreation();
        $service = new GoogleCalendarService($this->user);
        $googleEventId = 'google_event_to_delete';

        $serviceCalendar = new Google_Service_Calendar($this->googleClientMock);
        $eventsResourceMock = Mockery::mock(Google_Service_Calendar_Events::class);
        $eventsResourceMock->shouldReceive('delete')
            ->with('primary', $googleEventId)
            ->once();
        $serviceCalendar->events = $eventsResourceMock;

        $reflection = new \ReflectionClass($service);
        $serviceProperty = $reflection->getProperty('service');
        $serviceProperty->setAccessible(true);
        $serviceProperty->setValue($service, $serviceCalendar);

        $result = $service->deleteEvent($googleEventId);
        $this->assertTrue($result);
    }

    #[Test]
    public function delete_event_handles_410_gone_as_success()
    {
        $this->overrideGoogleClientCreation();
        Log::shouldReceive('info')->once();
        $service = new GoogleCalendarService($this->user);
        $googleEventId = 'already_gone_event_id';

        $googleServiceException = new Google_Service_Exception("Event already deleted (410)");
        $googleServiceException->setErrors([['reason' => 'deleted', 'message' => 'Resource already deleted']]); // Mimic Google API error structure

        $serviceCalendar = new Google_Service_Calendar($this->googleClientMock);
        $eventsResourceMock = Mockery::mock(Google_Service_Calendar_Events::class);
        $eventsResourceMock->shouldReceive('delete')
            ->with('primary', $googleEventId)
            ->once()
            ->andThrow($googleServiceException);
        $serviceCalendar->events = $eventsResourceMock;

        $reflection = new \ReflectionClass($service);
        $serviceProperty = $reflection->getProperty('service');
        $serviceProperty->setAccessible(true);
        $serviceProperty->setValue($service, $serviceCalendar);

        $result = $service->deleteEvent($googleEventId);
        $this->assertTrue($result);
    }


    #[Test]
    public function token_is_refreshed_if_expired()
    {
        $this->user->google_calendar_token_expires_at = Carbon::now()->subHour(); // Expired
        $this->user->save();

        $this->overrideGoogleClientCreation(true); // Signal token is expired for mocking purposes

        // We expect the user's tokens to be updated
        $this->user->shouldReceive('save')->once();

        $service = new GoogleCalendarService($this->user); // Triggers constructor and refresh logic

        // Assert that the token in the user model has been updated (mocked new token)
        // This requires more intricate mocking of the User model or checking DB after service call
        // For simplicity, we rely on the mock expectations for fetchAccessTokenWithRefreshToken and user->save()
        $decryptedNewToken = Crypt::decryptString($this->user->google_calendar_access_token);
        $this->assertEquals('new_access_token', $decryptedNewToken);
        $this->assertTrue($this->user->google_calendar_token_expires_at->isFuture());
    }

    #[Test]
    public function refresh_token_throws_exception_if_no_refresh_token_in_db()
    {
        $this->user->google_calendar_token_expires_at = Carbon::now()->subHour(); // Expired
        $this->user->google_calendar_refresh_token = null; // No refresh token
        $this->user->save();

        $this->overrideGoogleClientCreation(true, false, true); // Expired, not failing refresh call, but no actual refresh token in DB

        Log::shouldReceive('warning')->once();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Google Calendar refresh token is missing. Please re-authenticate.');

        new GoogleCalendarService($this->user);
    }

    #[Test]
    public function refresh_token_handles_api_failure_during_refresh()
    {
        $this->user->google_calendar_token_expires_at = Carbon::now()->subHour(); // Expired
        $this->user->save();

        $this->overrideGoogleClientCreation(true, true); // Expired and refresh call will fail

        Log::shouldReceive('error')->once();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Failed to refresh Google Calendar token: Failed to fetch. Please re-authenticate./');

        new GoogleCalendarService($this->user);
    }

}
