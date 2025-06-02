<?php

namespace Tests\Feature\Features;

use App\Models\User;
use Carbon\Carbon;
use Google_Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Illuminate\Encryption\Encrypter;
use PHPUnit\Framework\Attributes\Test; // Import the Test attribute

class GoogleOAuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected MockInterface $googleClientMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure APP_KEY and APP_CIPHER are explicitly set for testing encryption.
        // Use $this->app['config']->set() for more direct impact in test lifecycle.
        $cipher = 'AES-256-CBC'; // A standard Laravel cipher
        $this->app['config']->set('app.cipher', $cipher);
        $this->app['config']->set('app.key', 'base64:'.Encrypter::generateKey($cipher));


        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['access-api']);

        // Mock Google_Client
        $this->googleClientMock = Mockery::mock(Google_Client::class);
        $this->app->instance(Google_Client::class, $this->googleClientMock);

        // Set up mock Google credentials in config, as the controller will use these
        Config::set('services.google.client_id', 'test_google_client_id');
        Config::set('services.google.client_secret', 'test_google_client_secret');
        Config::set('services.google.redirect_uri', route('google.calendar.callback')); // Use named route for consistency
        Config::set('app.url', 'http://localhost'); // Ensure APP_URL is set for route generation
        Config::set('frontend_url', 'http://localhost:3000'); // For redirects
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function redirect_to_google_generates_correct_auth_url()
    {
        $expectedBaseUrl = 'https://accounts.google.com/o/oauth2/v2/auth';
        $expectedRedirectUri = urlencode(route('google.calendar.callback'));
        $expectedScope = urlencode('https://www.googleapis.com/auth/calendar https://www.googleapis.com/auth/calendar.events');

        $this->googleClientMock
            ->shouldReceive('setClientId')->with('test_google_client_id')->once()
            ->shouldReceive('setClientSecret')->with('test_google_client_secret')->once()
            ->shouldReceive('setRedirectUri')->with(route('google.calendar.callback'))->once()
            ->shouldReceive('setScopes')->with([
                'https://www.googleapis.com/auth/calendar',
                'https://www.googleapis.com/auth/calendar.events',
            ])->once()
            ->shouldReceive('setAccessType')->with('offline')->once()
            ->shouldReceive('setPrompt')->with('consent')->once()
            ->shouldReceive('createAuthUrl')->once()->andReturnUsing(function () use ($expectedBaseUrl, $expectedRedirectUri, $expectedScope) {
                // Construct a URL that the Google Client library would typically create, including a state parameter
                $state = session()->get('state'); // The Google Client would generate and store a state
                if (!$state) { // If not set by a previous step (which it wouldn't be in this isolated mock)
                    $state = 'mock_state_value'; // Use a placeholder for matching structure
                }
                return $expectedBaseUrl . '?response_type=code&access_type=offline&client_id=' . config('services.google.client_id') . '&redirect_uri=' . $expectedRedirectUri . '&state=' . $state . '&scope=' . $expectedScope . '&prompt=consent';
            });

        $response = $this->getJson(route('google.calendar.auth'));

        $redirectUrl = $response->headers->get('Location');
        $this->assertStringStartsWith($expectedBaseUrl, $redirectUrl);
        $this->assertStringContainsString('client_id=test_google_client_id', $redirectUrl);
        $this->assertStringContainsString('redirect_uri=' . $expectedRedirectUri, $redirectUrl);
        $this->assertStringContainsString('scope=' . $expectedScope, $redirectUrl);
        $this->assertStringContainsString('access_type=offline', $redirectUrl);
        $this->assertStringContainsString('prompt=consent', $redirectUrl);
        $this->assertStringContainsString('response_type=code', $redirectUrl);
        $this->assertStringContainsString('state=', $redirectUrl); // Check for presence of state param
    }

    #[Test]
    public function handle_google_callback_stores_tokens_and_redirects_on_success()
    {
        $authCode = 'test_auth_code';
        $mockAccessToken = [
            'access_token' => 'mock_access_token_value',
            'refresh_token' => 'mock_refresh_token_value',
            'expires_in' => 3600,
        ];

        $this->googleClientMock
            ->shouldReceive('setClientId')->with('test_google_client_id')->once()
            ->shouldReceive('setClientSecret')->with('test_google_client_secret')->once()
            ->shouldReceive('setRedirectUri')->with(route('google.calendar.callback'))->once()
            ->shouldReceive('setScopes')->withAnyArgs()->once() // Already tested in redirect, simplify here
            ->shouldReceive('setAccessType')->withAnyArgs()->once()
            ->shouldReceive('setPrompt')->withAnyArgs()->once()
            ->shouldReceive('fetchAccessTokenWithAuthCode')->with($authCode)->once()->andReturn($mockAccessToken);

        $response = $this->getJson(route('google.calendar.callback', ['code' => $authCode]));

        $response->assertRedirect(config('frontend_url') . '?google_auth_success=true');

        $this->user->refresh(); // Refresh user model from database

        $this->assertNotNull($this->user->google_calendar_access_token);
        $this->assertEquals('mock_access_token_value', Crypt::decryptString($this->user->google_calendar_access_token));
        $this->assertNotNull($this->user->google_calendar_refresh_token);
        $this->assertEquals('mock_refresh_token_value', Crypt::decryptString($this->user->google_calendar_refresh_token));
        $this->assertNotNull($this->user->google_calendar_token_expires_at);
        $this->assertTrue($this->user->google_calendar_token_expires_at->isFuture());
        // Check if it's approximately 1 hour from now
        $this->assertEquals(Carbon::now()->addSeconds(3600)->timestamp, $this->user->google_calendar_token_expires_at->timestamp, 5); // Allow 5s leeway
    }

    #[Test]
    public function handle_google_callback_redirects_with_error_if_google_returns_error()
    {
        $this->googleClientMock->shouldIgnoreMissing(); // Ignore other calls for this specific error test

        $response = $this->getJson(route('google.calendar.callback', ['error' => 'access_denied']));
        $response->assertRedirect(config('frontend_url') . '?google_auth_error=access_denied');
    }

    #[Test]
    public function handle_google_callback_redirects_with_error_if_code_is_missing()
    {
        $this->googleClientMock->shouldIgnoreMissing();
        $response = $this->getJson(route('google.calendar.callback')); // No code, no error
        $response->assertRedirect(config('frontend_url') . '?google_auth_error=missing_code');
    }


    #[Test]
    public function handle_google_callback_handles_token_fetch_exception()
    {
        $authCode = 'test_auth_code';
        $this->googleClientMock
            ->shouldReceive('setClientId')->withAnyArgs()->once()
            ->shouldReceive('setClientSecret')->withAnyArgs()->once()
            ->shouldReceive('setRedirectUri')->withAnyArgs()->once()
            ->shouldReceive('setScopes')->withAnyArgs()->once()
            ->shouldReceive('setAccessType')->withAnyArgs()->once()
            ->shouldReceive('setPrompt')->withAnyArgs()->once()
            ->shouldReceive('fetchAccessTokenWithAuthCode')->with($authCode)->once()->andThrow(new \Exception("Fetch failed"));

        $response = $this->getJson(route('google.calendar.callback', ['code' => $authCode]));
        $response->assertRedirect(config('frontend_url') . '?google_auth_error=token_fetch_failed');
    }

    #[Test]
    public function handle_google_callback_handles_error_in_access_token_response()
    {
        $authCode = 'test_auth_code';
        $mockAccessTokenWithError = [
            'error' => 'invalid_grant',
            'error_description' => 'Token has been expired or revoked.'
        ];

        $this->googleClientMock
            ->shouldReceive('setClientId')->withAnyArgs()->once()
            ->shouldReceive('setClientSecret')->withAnyArgs()->once()
            ->shouldReceive('setRedirectUri')->withAnyArgs()->once()
            ->shouldReceive('setScopes')->withAnyArgs()->once()
            ->shouldReceive('setAccessType')->withAnyArgs()->once()
            ->shouldReceive('setPrompt')->withAnyArgs()->once()
            ->shouldReceive('fetchAccessTokenWithAuthCode')->with($authCode)->once()->andReturn($mockAccessTokenWithError);

        $response = $this->getJson(route('google.calendar.callback', ['code' => $authCode]));
        $response->assertRedirect(config('frontend_url') . '?google_auth_error=invalid_grant');
    }
}
