<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use App\Services\HotScoreService;
use App\Services\ImageResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ModernNewsSeeder extends Seeder
{
    private array $sources = [
        'TechCrunch',
        'The Verge',
        'Reuters',
        'Bloomberg',
        'Wired',
        'Ars Technica',
        'The Information',
        'CNBC',
        'MIT Technology Review',
        'Nature',
        'Science Daily',
        'Financial Times',
    ];

    private array $headlines = [
        // AI & Tech (35%)
        'OpenAI Releases GPT-5 with Advanced Reasoning Capabilities',
        'Google DeepMind Achieves Breakthrough in Protein Folding Prediction',
        'Meta Announces Open-Source AI Model Rivaling GPT-4',
        'Anthropic Raises $2B Series C, Valuation Hits $18B',
        'New Study: AI Coding Assistants Boost Developer Productivity by 55%',
        'Microsoft Integrates AI Agents Across Entire Office Suite',
        'Apple Intelligence Gets Major Update with On-Device LLMs',
        'Laravel 12 Released with Native AI Integration Features',
        'React 20 Introduces Revolutionary Server Components Architecture',
        'TypeScript 6.0 Brings Pattern Matching and Nominal Types',
        'Bun 2.0 Now Faster Than Node.js by 4x in Production Benchmarks',
        'GitHub Copilot X: AI Writes Entire Applications from Descriptions',
        'Deno Surpasses 100K Stars, Challenges Node.js Dominance',
        'New Zero-Day Exploit Found in Popular JavaScript Library',
        'Google Patches Critical Chrome Security Vulnerability',
        'Hackers Breach Major Cloud Provider, 2M Records Exposed',
        'New Ransomware Strain Targets Docker Containers',
        'AWS Announces Graviton5 Chips with 60% Better Performance',
        'Vercel Raises $250M Series D, Valued at $3.2B',
        'Cloudflare Launches AI Gateway for Model Load Balancing',
        'Bitcoin Hits $85K as Institutional Adoption Accelerates',
        'Ethereum 3.0 Roadmap Promises 100x Scalability Improvement',
        'Coinbase Reports Record Trading Volume in Q2 2026',
        'Solana Network Achieves 1 Million TPS in Breakthrough Test',
        'Apple Unveils iPhone 17 with Revolutionary Holographic Display',
        'Samsung Galaxy Z Fold 7 Features Tri-Fold Design',
        'Google Pixel 10 Camera Uses AI to Capture Perfect Shots',
        'Nothing Phone 4 Sells Out in 12 Minutes',
        'AMD Ryzen 9000 Series Crushes Intel in Benchmarks',
        'NVIDIA GeForce RTX 6090 Announced, 3x Faster Ray Tracing',
        'Apple Vision Pro 2 Launches with Prescription Lens Support',
        'Framework Laptop 18 Brings Full Modularity to Desktop Replacement',
        'Tesla Optimus Robot Now Available for Pre-Order at $25K',
        'Sony PlayStation 6 Specs Leaked, 16K Gaming Confirmed',
        'Meta Quest 4 Achieves Photorealistic Mixed Reality',

        // Business & Finance (20%)
        'Y Combinator Winter 2026 Batch Raises $450M Collectively',
        'Stripe Acquires Fintech Startup Plaid for $8.5B',
        'Notion Reaches $15B Valuation After Series D Round',
        'Linear Disrupts Project Management with $2B Valuation',
        'Figma Revenue Crosses $1B ARR, IPO Expected in 2027',
        'How This Solo Founder Built a $50M ARR SaaS Business',
        'WeWork 2.0: Co-Working Giant Returns with $2B Funding',
        'Microsoft Acquires AI Security Startup for $2.3B',
        'Salesforce Announces Major Layoffs, Stock Drops 15%',
        'Amazon Reports Record Q2 Earnings, AWS Growth Accelerates',
        'Tesla Stock Surges 40% After Record Delivery Quarter',
        'SpaceX Valuation Reaches $180B in Secondary Share Sale',
        'Stripe Achieves Profitability, Processing $1.2T Annually',
        'PayPal Launches PYUSD Stablecoin Globally',
        'Robinhood Adds Crypto Staking, Attracts 5M New Users',
        'Square Rebrands to Block, Focuses on Bitcoin Infrastructure',
        'Google Antitrust Trial: DOJ Seeks Company Breakup',
        'Apple Fined $2B by EU for App Store Monopoly',
        'Meta Exits China Market After Regulatory Pressure',
        'Twitter Rebrand to X Complete, User Growth Stagnant',

        // Science (15%)
        'SpaceX Starship Completes First Crewed Mars Landing',
        'NASA Confirms Water Ice Discovery on Moon\'s South Pole',
        'James Webb Telescope Detects Potential Biosignatures',
        'Blue Origin Lunar Lander Successfully Reaches Moon Surface',
        'China\'s Space Station Welcomes International Crew',
        'CRISPR Treatment Cures Sickle Cell Disease in Clinical Trial',
        'Moderna\'s Cancer Vaccine Shows 89% Efficacy in Phase 3',
        'Neuralink Implant Restores Vision to Blind Patient',
        'Lab-Grown Organs Successfully Transplanted in Humans',
        'New Alzheimer\'s Drug Reverses Cognitive Decline',
        'Quantum Computer Breaks RSA-2048 Encryption Milestone',
        'Fusion Reactor Achieves Net Energy Gain for 10 Minutes',
        'Tesla Launches Grid-Scale Battery at $50/kWh',
        'Carbon Capture Technology Reaches Cost Parity',
        'Solar Panel Efficiency Hits 50% in Laboratory Setting',

        // Culture & Design (10%)
        'Figma Unveils AI-Powered Design System Generator',
        'Adobe Firefly 3 Creates Photorealistic Images from Text',
        'Canva Enterprise Reaches 50M Users Worldwide',
        'The State of Design Systems: 2026 Industry Report',
        'Why Designers Are Quitting Big Tech for Startups',
        'TikTok Algorithm Changes Upend Creator Economy',
        'YouTube Introduces AI-Generated Shorts, Creators Revolt',
        'Spotify Pays Out $10B to Artists in 2026',
        'Netflix Cancels Password Sharing Crackdown After Backlash',
        'Threads Surpasses 500M Users, Challenges Twitter/X',

        // Geopolitics (10%)
        'EU AI Act Takes Effect, Tech Giants Face Compliance Rush',
        'US Senate Passes Landmark AI Regulation Bill',
        'China Bans Foreign AI Models, Mandates Domestic Alternatives',
        'UK Launches $1B AI Safety Institute',
        'India Becomes Third Largest Tech Hub, Overtakes Germany',
        'Global Data Privacy Treaty Signed by 47 Nations',
        'Tech Cold War: US Expands Chip Export Restrictions',
        'Australia Passes Social Media Age Verification Law',
        'Brazil Fines Meta $200M for Data Privacy Violations',
        'Singapore Emerges as Asia\'s AI Innovation Leader',

        // Society (10%)
        '4-Day Work Week Trials Show 40% Productivity Increase',
        'Remote Work Becomes Legal Right in 12 EU Countries',
        'AI Replaces 30% of Customer Service Jobs in 2026',
        'Universal Basic Income Pilot Succeeds in Finland',
        'Gen Z Abandons Facebook Entirely, Instagram Usage Drops',
        'Digital Nomad Visas Issued to 5M Remote Workers',
        'Burnout Epidemic: Tech Workers Quit at Record Rates',
        'Education Crisis: AI Renders Traditional Testing Obsolete',
        'Mental Health Apps See 200% Usage Increase',
        'How Technology Is Reshaping Democracy in 2026',
    ];

    public function run(): void
    {
        $imageResolver = app(ImageResolver::class);
        $hotScoreService = app(HotScoreService::class);

        $users = User::query()->limit(10)->get();
        if ($users->isEmpty()) {
            $users = User::factory()->count(5)->create();
        }

        $topics = Topic::query()->get();
        if ($topics->isEmpty()) {
            $this->call(TopicSeeder::class);
            $topics = Topic::query()->get();
        }

        // Topic distribution: 35% tech, 20% business, 15% science, 10% culture, 10% geo, 10% society
        $topicDistribution = [
            'tech' => 35,
            'business' => 20,
            'science' => 15,
            'culture' => 10,
            'geo' => 10,
            'society' => 10,
        ];

        $topicsByCategory = [
            'tech' => $topics->whereIn('name', [
                'Artificial Intelligence',
                'Software Development',
                'Cybersecurity',
                'Cloud & Infrastructure',
                'Web3 & Blockchain',
                'Mobile Technology',
                'Hardware & Gadgets',
            ]),
            'business' => $topics->whereIn('name', [
                'Startups',
                'Business Strategy',
                'Finance & Markets',
                'Tech Industry',
            ]),
            'science' => $topics->whereIn('name', [
                'Space & Astronomy',
                'Biotechnology',
                'Climate Tech',
            ]),
            'culture' => $topics->whereIn('name', [
                'Design & UX',
                'Digital Culture',
            ]),
            'geo' => $topics->whereIn('name', [
                'Global Affairs',
                'Tech Policy',
            ]),
            'society' => $topics->whereIn('name', [
                'Future of Work',
                'Digital Society',
            ]),
        ];

        $topicImageCache = [];

        $this->command->info('Creating 100 modern tech news posts...');
        $progressBar = $this->command->getOutput()->createProgressBar(100);

        for ($i = 0; $i < 100; $i++) {
            // Select category based on distribution
            $category = $this->selectCategory($topicDistribution, $i);
            $categoryTopics = $topicsByCategory[$category];

            if ($categoryTopics->isEmpty()) {
                $categoryTopics = $topics;
            }

            $topic = $categoryTopics->random();

            // Select headline
            $title = $this->headlines[$i % count($this->headlines)];

            // Resolve per-article relevant image via entity/keyword mapping
            $images = $imageResolver->resolveAndStore($title, $topic->name);

            // Generate content
            $content = $this->generateNewsContent($title);

            // Realistic engagement metrics
            $voteScore = $this->generateVoteScore();
            $upvotes = max(0, $voteScore + rand(0, 30));
            $downvotes = max(0, $upvotes - $voteScore);
            $viewCount = rand(100, 25000);
            $commentCount = rand(0, 150);

            // Publish date distribution: more recent posts
            $daysAgo = $this->generatePublishDate();

            $post = Post::create([
                'user_id' => $users->random()->id,
                'topic_id' => $topic->id,
                'title' => $title,
                'slug' => Str::slug($title) . '-' . rand(1000, 9999),
                'content' => $content,
                'content_html' => Str::markdown($content),
                'url' => rand(1, 100) <= 30 ? 'https://example.com/article-' . rand(1000, 9999) : null,
                'canonical_url' => rand(1, 100) <= 30 ? 'https://example.com/article-' . rand(1000, 9999) : null,
                'post_type' => rand(1, 100) <= 30 ? 'link' : 'text',
                'image_url' => $images['image_url'],
                'thumbnail_url' => $images['thumbnail_url'],
                'meta_title' => Str::limit($title, 60),
                'meta_description' => Str::limit(strip_tags($content), 160),
                'vote_score' => $voteScore,
                'upvote_count' => $upvotes,
                'downvote_count' => $downvotes,
                'comment_count' => $commentCount,
                'view_count' => $viewCount,
                'bookmark_count' => rand(0, 100),
                'hot_score' => 0,
                'is_approved' => true,
                'status' => 'published',
                'published_at' => now()->subDays($daysAgo)->subHours(rand(0, 23))->subMinutes(rand(0, 59)),
                'last_activity_at' => now()->subDays(max(0, $daysAgo - rand(0, 2))),
                'source_name' => $this->sources[array_rand($this->sources)],
                'source_url' => 'https://example.com/source-' . rand(10000, 99999),
            ]);

            // Calculate hot score
            $hotScoreService->recalculate($post->fresh());

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->command->newLine();
        $this->command->info('✓ Created 100 modern tech news posts');
    }

    private function selectCategory(array $distribution, int $index): string
    {
        $position = $index % 100;

        $cumulative = 0;
        foreach ($distribution as $category => $percentage) {
            $cumulative += $percentage;
            if ($position < $cumulative) {
                return $category;
            }
        }

        return 'tech';
    }

    private function generateVoteScore(): int
    {
        $rand = rand(1, 100);

        if ($rand <= 50) {
            return rand(5, 100);
        } elseif ($rand <= 80) {
            return rand(101, 300);
        } elseif ($rand <= 95) {
            return rand(301, 800);
        } else {
            return rand(801, 1500);
        }
    }

    private function generatePublishDate(): int
    {
        $rand = rand(1, 100);

        if ($rand <= 40) {
            return rand(0, 2); // Last 2 days (40%)
        } elseif ($rand <= 70) {
            return rand(3, 7); // Last week (30%)
        } elseif ($rand <= 90) {
            return rand(8, 14); // Last 2 weeks (20%)
        } else {
            return rand(15, 30); // Last month (10%)
        }
    }

    private function generateNewsContent(string $title): string
    {
        $leads = [
            ucfirst($title) . ' has been announced, marking a significant shift across the technology landscape. The news was confirmed by multiple people familiar with the matter, and it has already reshaped expectations among industry observers.',
            ucfirst($title) . ' was confirmed earlier this week, drawing attention from investors, analysts, and developers alike. Early reactions have ranged from cautious optimism to outright excitement, with several firms adjusting their roadmaps within hours of the announcement.',
            ucfirst($title) . ' came into focus today as companies released fresh details about the move and its expected impact. The announcement follows months of speculation, and the scale of the change has taken many in the sector by surprise.',
            ucfirst($title) . ' has begun to ripple through the industry, prompting both enthusiasm and measured caution from observers. Supporters point to clear long-term benefits, while skeptics note that execution risks remain significant.',
            ucfirst($title) . ' is now underway, according to multiple people familiar with the plans. Internal teams have been working on the effort for some time, and today\u2019s announcement formalizes what was previously an open secret.',
            ucfirst($title) . ' is drawing growing interest after new information surfaced about the scope of the change. The details released so far suggest the initiative is far larger than initially reported.',
        ];

        $whySentence1 = [
            'the development signals a broader push toward efficiency and lower operating costs.',
            'it addresses rising demand from users who have long called for exactly this kind of change.',
            'the move positions the major players to capture a share of a market that analysts expect to grow sharply over the next several years.',
            'the underlying economics have finally shifted in a way that makes the approach practical and scalable.',
            'it reflects mounting competitive pressure across the sector and a race to lock in early adopters.',
            'regulators, customers, and partners have all been steering the industry in this direction for more than a year.',
        ];
        $whySentence2 = [
            'Industry analysts estimate the affected market could grow by double digits annually, which explains why so many companies are moving quickly rather than waiting to see how the situation evolves.',
            'For end users, the practical consequences are substantial: lower costs, faster delivery, and new capabilities that were previously available only to large enterprises.',
            'The timing is not accidental, either. Years of accumulated technical debt and shifting user expectations have made the current moment unusually favorable for change.',
            'Beyond the immediate benefits, the shift is expected to influence how competitors price their products and allocate engineering resources for the rest of the decade.',
            'This is not merely an incremental update. Observers argue it represents one of the most consequential changes the industry has seen in years, with effects that will compound over time.',
            'What makes it significant is the combination of timing and scale: the technology is mature enough to deploy widely, and the market conditions are finally ready to reward early movers.',
        ];

        $howSentence1 = [
            'new tooling was rolled out incrementally to a small group of testers before being expanded to a wider user base.',
            'the feature builds on existing infrastructure, reusing proven components rather than starting from scratch.',
            'teams first validated the approach internally, then opened it to a broader audience in stages.',
            'the work combines a redesigned core with a new integration layer, letting the system scale without reworking the entire stack.',
            'the rollout follows a phased plan, with early feedback shaping each subsequent release.',
            'the underlying system was built around a modular design, allowing individual pieces to be improved independently.',
        ];
        $howSentence2 = [
            'Engineering teams describe the implementation as deliberately conservative: each stage was tested against real workloads, and the most fragile components were replaced first to reduce the risk of regressions.',
            'Behind the scenes, the effort involved coordination across dozens of teams, with a shared set of internal standards keeping the different parts of the system compatible throughout the transition.',
            'The technical path was not straightforward. Early prototypes ran into performance bottlenecks, and the team spent months profiling and optimizing before the design proved itself at scale.',
            'What made the approach work was an emphasis on backward compatibility, allowing existing users to keep working normally while the new system was brought up alongside the old one.',
            'A dedicated integration layer smooths the transition, translating between the new architecture and the systems it replaces, which is why the change can proceed without a disruptive cutover.',
            'The rollout was designed around measurable milestones, with each phase gated on performance and reliability targets, a discipline that prevented the project from drifting during development.',
        ];

        $whoSentence1 = [
            'executives at the companies involved take the lead, while independent researchers contribute technical analysis.',
            'a small set of well-funded incumbents dominates the conversation, but newer entrants are moving quickly too.',
            'the developers and product teams closest to the work are the ones driving day-to-day decisions.',
            'policymakers are paying close attention, with several regulators already signaling interest in the outcome.',
            'a network of partners, suppliers, and early customers is shaping how the effort takes hold in practice.',
            'the user community itself is proving influential, with feedback loops guiding the direction of the work.',
        ];
        $whoSentence2 = [
            'Among the most prominent figures, several executives have staked their reputations on the initiative, appearing at industry events and defending the approach in public interviews.',
            'Universities and research labs are also involved, contributing independent evaluations that will determine whether the technology meets the claims made for it.',
            'The competitive dynamic matters here: if the leading players succeed, smaller firms will likely follow, while a failure could set the sector back and embolden critics.',
            'A coalition of advocacy groups has been monitoring the rollout closely, pressing for transparency and safeguards, and their input has already shaped several design decisions.',
            'The people closest to the work describe a mix of urgency and caution, with leadership pushing for speed while frontline teams insist on testing before every expansion.',
            'Investors are watching just as closely, and the funding decisions made in response to this announcement will likely influence the direction of the market for years to come.',
        ];

        $timelineSentence1 = [
            'plans for this effort first surfaced publicly several months ago, and the most recent milestone arrived within the last few days.',
            'early signals appeared last quarter, followed by a formal announcement and a limited rollout.',
            'the initiative moved from initial research into active development over roughly six months.',
            'work began quietly about a year ago and gained momentum through the first half of 2026.',
            'the first concrete hints emerged two quarters ago, and the project has advanced steadily since.',
        ];
        $timelineSentence2 = [
            'The team expects the next major update before the end of the year, with broader availability scheduled to follow shortly afterward.',
            'Broader availability is expected in the coming weeks, assuming the remaining pilot deployments stay on schedule.',
            'The current phase focuses on stability and wider access, and a public launch is planned for the next quarter.',
            'With this release now public, the next milestone on the calendar is a scaled deployment, tentatively set for early next year.',
            'The next round of details is expected within the next month, along with fresh guidance on pricing and eligibility.',
        ];

        $closings = [
            'As the rollout continues, all eyes will be on how quickly the approach gains traction among the people it is meant to serve.',
            'The coming months will reveal whether the initial optimism translates into lasting, measurable results.',
            'For now, the development stands as another sign of how fast the sector is moving and how quickly expectations are resetting.',
            'Competitors are likely to respond, and the resulting back and forth may shape the market for the rest of the year.',
            'Whether the momentum holds depends on execution, but the direction of travel is now unmistakable.',
        ];

        $lead = $leads[array_rand($leads)];
        $why = $whySentence1[array_rand($whySentence1)] . ' ' . $whySentence2[array_rand($whySentence2)];
        $how = $howSentence1[array_rand($howSentence1)] . ' ' . $howSentence2[array_rand($howSentence2)];
        $who = $whoSentence1[array_rand($whoSentence1)] . ' ' . $whoSentence2[array_rand($whoSentence2)];
        $timeline = $timelineSentence1[array_rand($timelineSentence1)] . ' ' . $timelineSentence2[array_rand($timelineSentence2)];
        $closing = $closings[array_rand($closings)];

        return implode("\n\n", [
            $lead,
            "## Why This Matters\n\n" . $why,
            "## How It Happened\n\n" . $how,
            "## Who Is Involved\n\n" . $who,
            "## Timeline\n\n" . $timeline,
            $closing,
        ]);
    }
}
