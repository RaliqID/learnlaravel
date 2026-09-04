<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@laranews.test',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'username' => 'editor',
            'email' => 'editor@laranews.test',
            'role' => 'moderator',
        ]);

        User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        $this->call([
            TopicSeeder::class,
            ModernNewsSeeder::class,
        ]);
    }
}
