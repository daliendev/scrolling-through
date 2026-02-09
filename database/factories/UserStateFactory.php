<?php

namespace Database\Factories;

use App\Domain\Books\Models\Book;
use App\Domain\Reading\Models\UserState;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Reading\Models\UserState>
 */
class UserStateFactory extends Factory
{
    protected $model = UserState::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'current_post_id' => null,
            'starred_post_ids' => [],
            'notes' => [],
            'posts_read' => 0,
        ];
    }
}
