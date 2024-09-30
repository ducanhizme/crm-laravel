<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    public function definition(): array
    {
        return [
            'token' => Str::random(10),
            'email' => $this->faker->unique()->safeEmail(),
            'expires_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'user_id' => User::factory(),
            'workspace_id' => Workspace::factory(),
        ];
    }
}
