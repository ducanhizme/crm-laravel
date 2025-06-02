<?php

namespace Tests\Feature;

use App\Models\User; // Added
use Illuminate\Foundation\Testing\RefreshDatabase; // Added
use Laravel\Sanctum\Sanctum; // Added
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test; // Import the Test attribute

class ExampleTest extends TestCase
{
    use RefreshDatabase; // Added

    /**
     * A basic test example.
     */
    #[Test]
    public function application_returns_user_data_for_authenticated_user_via_api_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user'); // Changed from '/' to '/api/user'

        $response->assertStatus(200) // Should be 200 if authenticated
                 ->assertJsonFragment(['email' => $user->email]); // Check for some user data
    }
}
