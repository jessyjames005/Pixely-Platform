<?php

declare(strict_types=1);

namespace Tests\Feature\Extensions;

use App\Extensions\Gallery\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PhotoModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_a_photo(): void
    {
        $photo = Photo::create([
            'title' => 'Sunset',
            'filename' => 'sunset.jpg',
        ]);

        $this->assertDatabaseHas('photos', [
            'title' => 'Sunset',
            'filename' => 'sunset.jpg',
        ]);

        $this->assertInstanceOf(Photo::class, $photo);
    }
}
