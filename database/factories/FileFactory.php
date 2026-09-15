<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Extensions\Files\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for uploaded files.
 *
 * @extends Factory<File>
 */
final class FileFactory extends Factory
{
    /**
     * The model corresponding to the factory.
     *
     * @var class-string<File>
     */
    protected $model = File::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->slug(3);

        return [
            'disk' => 'public',
            'path' => "files/{$name}.jpg",
            'thumbnail_path' => "files/thumbnails/{$name}.jpg",
            'original_name' => "{$name}.jpg",
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(1_000, 4_000_000),
            'uploaded_by' => null,
        ];
    }
}
