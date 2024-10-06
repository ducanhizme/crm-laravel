<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'address' => $this->faker->address(),
            'domain_name' => $this->faker->name(),
            'employees' => $this->faker->randomNumber(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'created_by' => User::factory(),
            'workspace_id' => Workspace::factory(),
        ];
    }
}
