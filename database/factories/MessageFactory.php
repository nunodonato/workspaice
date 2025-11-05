<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'content' => $this->faker->sentence(),
            'role' => $this->faker->randomElement(['user', 'assistant', 'tool_use', 'tool_result']),
            'name' => null,
            'tool_id' => null,
            'input' => null,
            'multiple' => false,
        ];
    }

    public function user(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'user',
        ]);
    }

    public function assistant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'assistant',
        ]);
    }

    public function toolUse(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'tool_use',
            'tool_id' => $this->faker->uuid(),
            'input' => ['key' => 'value'],
        ]);
    }

    public function toolResult(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'tool_result',
            'tool_id' => $this->faker->uuid(),
        ]);
    }

    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'error',
        ]);
    }
}
