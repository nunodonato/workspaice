<?php

namespace Database\Factories;

use App\Models\Call;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class CallFactory extends Factory
{
    protected $model = Call::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'call' => json_encode(['messages' => []]),
            'response' => [
                'type' => 'message',
                'content' => [['type' => 'text', 'text' => $this->faker->sentence()]],
            ],
            'prompt_tokens' => $this->faker->numberBetween(100, 5000),
            'completion_tokens' => $this->faker->numberBetween(50, 2000),
            'message_count' => $this->faker->numberBetween(1, 10),
        ];
    }
}
