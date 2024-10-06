<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\People;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PeopleFactory extends Factory
{
    protected $model = People::class;

    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'job_title' => $this->faker->word(),
            'phones' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
            'created_by' => User::factory(),
        ];
    }
}
