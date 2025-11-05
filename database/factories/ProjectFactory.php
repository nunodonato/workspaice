<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->paragraph(),
            'technical_specs' => $this->faker->sentence(),
            'system_description' => $this->faker->sentence(),
            'full_path' => '/tmp/'.Str::slug($name),
            'tasks' => "✅ Initial task\n⬜ Pending task",
            'notes' => $this->faker->optional()->paragraph(),
            'files' => [],
        ];
    }
}
