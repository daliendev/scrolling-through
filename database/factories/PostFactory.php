<?php

namespace Database\Factories;

use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Books\Models\Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'text' => fake()->paragraph(),
            'type' => 'paragraph',
            'chapter_title' => null,
            'position' => 0,
        ];
    }

    public function chapter(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'chapter',
            'chapter_title' => fake()->sentence(3),
            'text' => fake()->sentence(3),
        ]);
    }
}
