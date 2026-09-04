<?php

namespace Database\Seeders;

use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\CommentVote;
use App\Models\Post;
use App\Models\PostVote;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private array $realisticNames = [
        ['Elena Rostova', 'elena_rostova'],
        ['Marcus Thorne', 'marcus_thorne'],
        ['Aisha Patel', 'aisha_patel'],
        ['Dmitri Volkov', 'dmitri_volkov'],
        ['Sophia Chen', 'sophia_chen'],
        ['James O\'Connor', 'james_oconnor'],
        ['Priya Sharma', 'priya_sharma'],
        ['Lucas Weber', 'lucas_weber'],
        ['Nina Kowalski', 'nina_kowalski'],
        ['Rafael Silva', 'rafael_silva'],
        ['Yuki Tanaka', 'yuki_tanaka'],
        ['Isabella Romano', 'isabella_romano'],
        ['Omar Hassan', 'omar_hassan'],
        ['Freya Nielsen', 'freya_nielsen'],
        ['Diego Martinez', 'diego_martinez'],
        ['Amara Okafor', 'amara_okafor'],
        ['Sebastian Müller', 'sebastian_muller'],
        ['Leila Abbasi', 'leila_abbasi'],
        ['Kai Yamamoto', 'kai_yamamoto'],
        ['Zara Ahmed', 'zara_ahmed'],
        ['Viktor Petrov', 'viktor_petrov'],
        ['Maya Gupta', 'maya_gupta'],
        ['Alexander Kim', 'alexander_kim'],
        ['Nora Eriksson', 'nora_eriksson'],
        ['Hassan Ali', 'hassan_ali'],
        ['Chloe Dubois', 'chloe_dubois'],
        ['Arjun Reddy', 'arjun_reddy'],
        ['Lena Ivanova', 'lena_ivanova'],
    ];

    private array $topicsData = [
        ['Technology', 'Latest in tech, software, hardware, and innovations'],
        ['Business', 'Entrepreneurship, markets, startups, and corporate news'],
        ['Science', 'Research, discoveries, and scientific breakthroughs'],
        ['AI & Machine Learning', 'Artificial intelligence, ML models, and automation'],
        ['Web Development', 'Frontend, backend, frameworks, and web technologies'],
        ['Cryptocurrency', 'Blockchain, Bitcoin, DeFi, and digital assets'],
        ['Gaming', 'Video games, esports, game development, and industry news'],
        ['Design', 'UI/UX, graphic design, product design, and creative work'],
        ['Startups', 'Startup culture, funding, growth, and founder stories'],
        ['Climate & Environment', 'Sustainability, climate change, and green tech'],
        ['Health & Medicine', 'Medical research, wellness, and healthcare innovation'],
        ['Politics & Society', 'Current events, policy, and social issues'],
        ['Education', 'Learning, courses, teaching, and educational technology'],
        ['Culture & Arts', 'Books, movies, music, and cultural commentary'],
    ];

    private array $postTitles = [
        'Breaking: Major AI Lab Announces Breakthrough in Reasoning Models',
        'The Future of Remote Work: What 2026 Data Shows',
        "Why Your Startup's First Hire Should Be a Designer",
        'New JavaScript Framework Claims to Replace React (Again)',
        'Climate Tech Funding Hits All-Time High in Q2 2026',
        'The Rise of Decentralized Social Networks',
        'How We Scaled to 10M Users Without VC Funding',
        'Understanding the Latest EU AI Regulation Changes',
        'The End of the Password Era? Passkeys Adoption Reaches 60%',
        'Inside the $10B Acquisition That Everyone Missed',
        'Why Developers Are Leaving Big Tech for Web3 Startups',
        'The Unexpected Revival of Desktop Applications',
        'New Study: 4-Day Work Week Increases Productivity by 40%',
        'How This 19-Year-Old Built a $50M ARR SaaS Solo',
        'The Dark Side of AI-Generated Content Nobody Talks About',
        'Kubernetes vs. Serverless: A 2026 Reality Check',
        'Why Your API Design Is Probably Wrong',
        'The State of TypeScript: Community Survey Results',
        'Breaking Down the Latest OpenAI Model Capabilities',
        'How to Build a Design System That Developers Actually Use',
        'The Untold Story Behind the Biggest Tech Layoffs of 2026',
        'Why Monorepos Are Making a Comeback',
        'The Future of Energy Storage: New Battery Tech Explained',
        'Inside the Fight for Net Neutrality in 2026',
        'How We Reduced Cloud Costs by 80% With This Simple Change',
        'The Psychology Behind Addictive UX Patterns',
        'New Programming Language Promises Memory Safety Without Garbage Collection',
        'Why Every Tech Company Is Building an AI Assistant',
        'The Hidden Environmental Cost of Cryptocurrency Mining',
        'How to Negotiate Your Tech Salary in 2026',
    ];

    public function run(): void
    {
        $this->command->info('🌱 Starting DemoDataSeeder...');

        // 1. Create Users
        $this->command->info('Creating users...');
        $users = collect();
        
        foreach ($this->realisticNames as $index => $nameData) {
            $factory = User::factory();
            
            // First 2 are moderators
            if ($index < 2) {
                $factory = $factory->moderator();
            }

            $user = $factory->create([
                'display_name' => $nameData[0],
                'username' => $nameData[1],
                'email' => $nameData[1] . '@example.com',
                'bio' => fake()->optional(0.7)->sentence(),
            ]);
            
            // Update karma_score directly (not mass-assignable)
            $user->update(['karma_score' => rand(10, 5000)]);
            
            $users->push($user);
        }
        $this->command->info("✓ Created {$users->count()} users");

        // 2. Create Topics
        $this->command->info('Creating topics...');
        $topics = collect();
        
        foreach ($this->topicsData as $topicData) {
            $topics->push(Topic::create([
                'name' => $topicData[0],
                'slug' => Str::slug($topicData[0]),
                'description' => $topicData[1],
                'is_active' => true,
                'subscriber_count' => rand(100, 15000),
                'post_count' => 0, // Will be updated
            ]));
        }
        $this->command->info("✓ Created {$topics->count()} topics");

        // 3. Create Posts
        $this->command->info('Creating posts...');
        $posts = collect();
        $postCount = rand(120, 150);
        
        // Distribution: 60% text, 30% link, 10% image
        $textCount = (int)($postCount * 0.6);
        $linkCount = (int)($postCount * 0.3);
        $imageCount = $postCount - $textCount - $linkCount;

        // Create posts with varied distribution
        for ($i = 0; $i < $postCount; $i++) {
            $type = 'text';
            if ($i >= $textCount && $i < $textCount + $linkCount) {
                $type = 'link';
            } elseif ($i >= $textCount + $linkCount) {
                $type = 'image';
            }

            // Realistic vote score distribution
            $voteScore = $this->generateRealisticVoteScore();
            $upvotes = max(0, $voteScore + rand(0, 20));
            $downvotes = max(0, $upvotes - $voteScore);

            // More recent posts
            $daysAgo = $this->generateRealisticPublishDate();
            
            $title = $this->generatePostTitle($i);
            $content = $type === 'text' ? $this->generatePostContent() : null;

            $post = Post::create([
                'user_id' => $users->random()->id,
                'topic_id' => $topics->random()->id,
                'title' => $title,
                'slug' => Str::slug($title) . '-' . rand(1000, 9999),
                'content' => $content,
                'content_html' => $content ? Str::markdown($content) : null,
                'url' => $type === 'link' ? fake()->url() : null,
                'canonical_url' => $type === 'link' ? fake()->url() : null,
                'post_type' => $type,
                'meta_title' => Str::limit($title, 60),
                'meta_description' => $content ? Str::limit(strip_tags($content), 160) : fake()->sentence(),
                'vote_score' => $voteScore,
                'upvote_count' => $upvotes,
                'downvote_count' => $downvotes,
                'comment_count' => 0, // Will be updated
                'view_count' => rand(50, 15000),
                'bookmark_count' => 0, // Will be updated
                'hot_score' => rand(0, 100) / 10,
                'is_approved' => true,
                'status' => 'published',
                'published_at' => now()->subDays($daysAgo)->subHours(rand(0, 23)),
                'last_activity_at' => now()->subDays(max(0, $daysAgo - rand(0, 3))),
            ]);

            $posts->push($post);
        }
        $this->command->info("✓ Created {$posts->count()} posts");

        // 4. Mark featured posts (3-5 high-scoring posts)
        $this->command->info('Setting featured posts...');
        $featuredPosts = $posts->sortByDesc('vote_score')->take(rand(3, 5));
        $editor = $users->where('role', 'moderator')->first();
        
        foreach ($featuredPosts as $post) {
            $post->update([
                'featured_at' => now()->subDays(rand(0, 7)),
                'featured_by' => $editor->id,
            ]);
        }
        $this->command->info("✓ Marked {$featuredPosts->count()} posts as featured");

        // 5. Mark breaking/trending posts (5-8 posts with is_pinned)
        $this->command->info('Setting breaking posts...');
        $breakingPosts = $posts->sortByDesc('view_count')->take(rand(5, 8));
        
        foreach ($breakingPosts as $post) {
            $post->update(['is_pinned' => true]);
        }
        $this->command->info("✓ Marked {$breakingPosts->count()} posts as breaking/pinned");

        // 6. Create Comments
        $this->command->info('Creating comments...');
        $comments = collect();
        $commentCount = rand(250, 400);
        
        // Most comments on popular posts
        $popularPosts = $posts->sortByDesc('vote_score')->take((int)($posts->count() * 0.4));
        
        for ($i = 0; $i < $commentCount; $i++) {
            // 70% on popular posts, 30% on others
            $targetPost = rand(1, 100) <= 70 ? $popularPosts->random() : $posts->random();
            
            // 20% chance of being a reply
            $parentComment = null;
            $depth = 0;
            if (rand(1, 100) <= 20 && $comments->where('post_id', $targetPost->id)->count() > 0) {
                $parentComment = $comments->where('post_id', $targetPost->id)->random();
                $depth = min(5, $parentComment->depth + 1);
            }

            $voteScore = rand(-5, 150);
            $upvotes = max(0, $voteScore + rand(0, 10));
            $downvotes = max(0, $upvotes - $voteScore);

            $comment = Comment::create([
                'user_id' => $users->random()->id,
                'post_id' => $targetPost->id,
                'parent_id' => $parentComment?->id,
                'content' => $this->generateCommentContent(),
                'vote_score' => $voteScore,
                'upvote_count' => $upvotes,
                'downvote_count' => $downvotes,
                'depth' => $depth,
                'is_deleted' => false,
            ]);

            $comments->push($comment);
        }
        $this->command->info("✓ Created {$comments->count()} comments");

        // Update post comment counts
        foreach ($posts as $post) {
            $post->update(['comment_count' => Comment::where('post_id', $post->id)->count()]);
        }

        // 7. Create Post Votes
        $this->command->info('Creating post votes...');
        $voteCount = rand(400, 600);
        
        for ($i = 0; $i < $voteCount; $i++) {
            try {
                PostVote::create([
                    'post_id' => $posts->random()->id,
                    'user_id' => $users->random()->id,
                    'vote_type' => rand(1, 100) <= 75 ? 1 : -1, // 75% upvotes
                ]);
            } catch (\Exception $e) {
                // Skip duplicate user-post votes
                continue;
            }
        }
        $actualPostVotes = PostVote::count();
        $this->command->info("✓ Created {$actualPostVotes} post votes");

        // 8. Create Comment Votes
        $this->command->info('Creating comment votes...');
        $commentVoteCount = rand(200, 300);
        
        for ($i = 0; $i < $commentVoteCount; $i++) {
            try {
                CommentVote::create([
                    'comment_id' => $comments->random()->id,
                    'user_id' => $users->random()->id,
                    'vote_type' => rand(1, 100) <= 80 ? 1 : -1, // 80% upvotes
                ]);
            } catch (\Exception $e) {
                // Skip duplicate user-comment votes
                continue;
            }
        }
        $actualCommentVotes = CommentVote::count();
        $this->command->info("✓ Created {$actualCommentVotes} comment votes");

        // 9. Create Bookmarks
        $this->command->info('Creating bookmarks...');
        $bookmarkCount = rand(50, 100);
        
        for ($i = 0; $i < $bookmarkCount; $i++) {
            try {
                Bookmark::create([
                    'user_id' => $users->random()->id,
                    'post_id' => $posts->random()->id,
                    'collection_name' => fake()->optional(0.3)->randomElement(['Read Later', 'Favorites', 'Tech', 'Inspiration']),
                    'notes' => fake()->optional(0.4)->sentence(),
                ]);
            } catch (\Exception $e) {
                // Skip duplicate user-post bookmarks
                continue;
            }
        }
        $actualBookmarks = Bookmark::count();
        $this->command->info("✓ Created {$actualBookmarks} bookmarks");

        // Update post bookmark counts
        foreach ($posts as $post) {
            $post->update(['bookmark_count' => Bookmark::where('post_id', $post->id)->count()]);
        }

        $this->command->info('✅ Demo data seeding complete!');
    }

    private function generateRealisticVoteScore(): int
    {
        $rand = rand(1, 100);
        
        // Distribution: most posts 0-50, some 50-200, few 200-800, rare negative
        if ($rand <= 60) {
            return rand(0, 50);
        } elseif ($rand <= 85) {
            return rand(51, 200);
        } elseif ($rand <= 95) {
            return rand(201, 500);
        } elseif ($rand <= 98) {
            return rand(501, 800);
        } else {
            return rand(-10, -1);
        }
    }

    private function generateRealisticPublishDate(): int
    {
        $rand = rand(1, 100);
        
        // More recent = more posts
        if ($rand <= 30) {
            return rand(0, 3); // Last 3 days
        } elseif ($rand <= 60) {
            return rand(4, 7); // Last week
        } elseif ($rand <= 85) {
            return rand(8, 14); // Last 2 weeks
        } else {
            return rand(15, 30); // Last month
        }
    }

    private function generatePostTitle(int $index): string
    {
        if ($index < count($this->postTitles)) {
            return $this->postTitles[$index];
        }

        // Fallback to generated titles
        $patterns = [
            'How {company} is revolutionizing {industry}',
            'The complete guide to {topic} in {year}',
            '{number} ways to improve your {skill}',
            'Why {technology} is the future of {field}',
            'Inside {company}\'s new {product} launch',
            'The rise and fall of {trend}',
            'What we learned from building {product}',
            'Understanding {concept}: A developer\'s perspective',
            'The hidden costs of {technology}',
            '{company} just announced {product} and it\'s game-changing',
        ];

        $replacements = [
            '{company}' => ['Meta', 'Google', 'Microsoft', 'Apple', 'Amazon', 'Tesla', 'OpenAI', 'Anthropic'],
            '{industry}' => ['healthcare', 'education', 'finance', 'manufacturing', 'transportation'],
            '{topic}' => ['web development', 'machine learning', 'cloud architecture', 'security', 'DevOps'],
            '{year}' => ['2026', '2027'],
            '{number}' => ['5', '7', '10', '12'],
            '{skill}' => ['coding', 'design', 'leadership', 'productivity', 'communication'],
            '{technology}' => ['WebAssembly', 'Edge Computing', 'Quantum Computing', 'AR/VR', '5G'],
            '{field}' => ['software development', 'data science', 'cybersecurity', 'UX design'],
            '{product}' => ['API', 'platform', 'SDK', 'framework', 'tool'],
            '{trend}' => ['cryptocurrency', 'NFTs', 'the metaverse', 'web3', 'AI agents'],
            '{concept}' => ['microservices', 'serverless', 'JAMstack', 'edge functions', 'zero-trust security'],
        ];

        $pattern = $patterns[array_rand($patterns)];
        
        foreach ($replacements as $placeholder => $options) {
            if (str_contains($pattern, $placeholder)) {
                $pattern = str_replace($placeholder, $options[array_rand($options)], $pattern);
            }
        }

        return $pattern;
    }

    private function generatePostContent(): string
    {
        $paragraphs = [];
        $numParagraphs = rand(2, 5);
        
        for ($i = 0; $i < $numParagraphs; $i++) {
            $paragraphs[] = fake()->paragraph(rand(3, 6));
        }

        return implode("\n\n", $paragraphs);
    }

    private function generateCommentContent(): string
    {
        $patterns = [
            'Great article! ' . fake()->sentence(),
            'I disagree with this take. ' . fake()->sentence(),
            'This is exactly what I needed to hear. Thanks for sharing!',
            fake()->sentence() . ' ' . fake()->sentence(),
            'Anyone else experiencing this issue?',
            'Thanks for the detailed explanation!',
            'Not sure I understand the point here. ' . fake()->sentence(),
            'This has been my experience as well. ' . fake()->sentence(),
            'Interesting perspective. ' . fake()->sentence(),
            'Source? ' . fake()->sentence(),
        ];

        return $patterns[array_rand($patterns)];
    }
}
