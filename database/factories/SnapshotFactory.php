<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Snapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

class SnapshotFactory extends Factory
{
    protected $model = Snapshot::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'commit' => $this->faker->sha1(),
            'description' => $this->faker->sentence(),
        ];
    }
}
