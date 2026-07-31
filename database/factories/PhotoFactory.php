<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Extensions\Gallery\Models\Photo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for Gallery photos.
 *
 * @extends Factory<Photo>
 */
final class PhotoFactory extends Factory
{
    /**
     * The model corresponding to the factory.
     *
     * @var class-string<Photo>
     */
    protected $model = Photo::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'filename' => fake()->uuid().'.jpg',
        ];
    }
}
