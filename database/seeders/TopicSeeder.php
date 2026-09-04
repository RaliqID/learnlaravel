<?php

namespace Database\Seeders;

use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TopicSeeder extends Seeder
{
    private const TOPICS = [
        // Technology 35% (7 topics)
        ['name' => 'Artificial Intelligence', 'description' => 'AI, machine learning, and neural networks'],
        ['name' => 'Software Development', 'description' => 'Programming, frameworks, and developer tools'],
        ['name' => 'Cybersecurity', 'description' => 'Information security and digital safety'],
        ['name' => 'Cloud & Infrastructure', 'description' => 'Cloud computing, DevOps, and infrastructure'],
        ['name' => 'Web3 & Blockchain', 'description' => 'Decentralized tech and cryptocurrency'],
        ['name' => 'Mobile Technology', 'description' => 'iOS, Android, and mobile innovation'],
        ['name' => 'Hardware & Gadgets', 'description' => 'Latest devices and hardware tech'],

        // Business & Finance 20% (4 topics)
        ['name' => 'Startups', 'description' => 'Startup news, funding, and entrepreneurship'],
        ['name' => 'Business Strategy', 'description' => 'Corporate news and business analysis'],
        ['name' => 'Finance & Markets', 'description' => 'Financial technology and market trends'],
        ['name' => 'Tech Industry', 'description' => 'Big tech companies and industry news'],

        // Science 15% (3 topics)
        ['name' => 'Space & Astronomy', 'description' => 'Space exploration and astronomy'],
        ['name' => 'Biotechnology', 'description' => 'Biotech and medical technology'],
        ['name' => 'Climate Tech', 'description' => 'Climate change and green technology'],

        // Culture & Design 10% (2 topics)
        ['name' => 'Design & UX', 'description' => 'Product design and user experience'],
        ['name' => 'Digital Culture', 'description' => 'Internet culture and digital trends'],

        // Geopolitics 10% (2 topics)
        ['name' => 'Global Affairs', 'description' => 'International technology policy'],
        ['name' => 'Tech Policy', 'description' => 'Regulation and technology governance'],

        // Society 10% (2 topics)
        ['name' => 'Future of Work', 'description' => 'Remote work and workplace technology'],
        ['name' => 'Digital Society', 'description' => 'Technology impact on society'],
    ];

    public function run(): void
    {
        foreach (self::TOPICS as $index => $topic) {
            Topic::query()->updateOrCreate(
                ['slug' => Str::slug($topic['name'])],
                [
                    'name' => $topic['name'],
                    'description' => $topic['description'],
                    'subscriber_count' => fake()->numberBetween(100, 50000),
                    'post_count' => fake()->numberBetween(10, 2000),
                    'is_active' => true,
                ]
            );
        }
    }
}
