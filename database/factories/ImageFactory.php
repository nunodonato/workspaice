<?php

namespace Database\Factories;

use App\Models\Image;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImageFactory extends Factory
{
    protected $model = Image::class;

    public function definition(): array
    {
        return [
            'message_id' => Message::factory(),
            'media_type' => $this->faker->randomElement(['image/jpeg', 'image/png', 'image/gif', 'image/webp']),
            'content' => base64_encode($this->faker->text(100)),
        ];
    }
}
