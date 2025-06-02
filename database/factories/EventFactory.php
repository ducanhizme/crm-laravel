<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'workspace_id' => Workspace::factory(),
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'event_type' => $this->faker->randomElement(['meeting', 'task', 'reminder', 'call']),
            'start_time' => Carbon::now()->addDays($this->faker->numberBetween(1, 10)),
            'end_time' => function (array $attributes) {
                // Ensure end_time is after start_time
                return Carbon::parse($attributes['start_time'])->addHours($this->faker->numberBetween(1, 3));
            },
            'google_calendar_event_id' => null, // Default to null
        ];
    }
}
