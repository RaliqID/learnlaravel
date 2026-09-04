# Coding Standards

## General Principles
1. **Readability First**: Code is read more than written
2. **Consistency**: Follow established patterns
3. **Simplicity**: Simple solutions over clever ones
4. **DRY**: Don't Repeat Yourself
5. **SOLID**: Follow SOLID principles
6. **KISS**: Keep It Simple, Stupid
7. **YAGNI**: You Aren't Gonna Need It

## PHP Standards

### PSR Compliance
- **PSR-1**: Basic coding standard
- **PSR-12**: Extended coding style guide
- **PSR-4**: Autoloading standard

### PHP Version
- Minimum: PHP 8.2
- Use modern PHP features (typed properties, enums, etc.)

### Naming Conventions

#### Classes
```php
// PascalCase
class PostController {}
class UserService {}
class PostRepository {}
```

#### Methods
```php
// camelCase
public function getUserPosts() {}
public function createPost() {}
```

#### Variables
```php
// camelCase
$userName = 'John';
$postCount = 10;
```

#### Constants
```php
// UPPER_SNAKE_CASE
const MAX_POST_LENGTH = 10000;
const DEFAULT_PAGE_SIZE = 20;
```

### Type Declarations
```php
// Always use type hints
public function createPost(string $title, int $userId): Post
{
    //
}

// Use return types
public function getPost(int $id): ?Post
{
    //
}

// Use property types
private string $title;
private ?int $score = null;
```

### Docblocks
```php
/**
 * Create a new post
 *
 * @param  string  $title
 * @param  int  $userId
 * @return Post
 * @throws ValidationException
 */
public function createPost(string $title, int $userId): Post
{
    //
}
```

## Laravel Conventions

### Controllers

#### Naming
```php
// Singular resource name + Controller
PostController
UserController
CommentController
```

#### Methods (Resource Controllers)
```php
index()    // Display listing
create()   // Show create form
store()    // Store new resource
show()     // Display single resource
edit()     // Show edit form
update()   // Update resource
destroy()  // Delete resource
```

#### Single Action Controllers
```php
class UpvotePostController
{
    public function __invoke(Post $post)
    {
        //
    }
}
```

#### Controller Structure
```php
class PostController extends Controller
{
    public function __construct(
        private PostService $postService
    ) {}

    public function index()
    {
        $posts = $this->postService->getHomeFeed();
        
        return view('posts.index', compact('posts'));
    }
}
```

### Models

#### Naming
```php
// Singular, PascalCase
Post
User
Comment
Topic
```

#### Model Structure
```php
class Post extends Model
{
    use HasFactory, SoftDeletes;

    // Table name (if not following convention)
    protected $table = 'posts';

    // Fillable attributes
    protected $fillable = [
        'title',
        'content',
        'user_id',
    ];

    // Hidden attributes
    protected $hidden = [
        'deleted_at',
    ];

    // Casts
    protected $casts = [
        'published_at' => 'datetime',
        'is_pinned' => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    // Accessors
    public function getExcerptAttribute(): string
    {
        return Str::limit($this->content, 200);
    }

    // Mutators
    public function setTitleAttribute($value): void
    {
        $this->attributes['title'] = Str::title($value);
    }
}
```

### Migrations

#### Naming
```php
// Timestamp + descriptive name
2026_08_05_create_posts_table.php
2026_08_05_add_score_to_posts_table.php
```

#### Migration Structure
```php
public function up(): void
{
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('topic_id')->constrained()->onDelete('cascade');
        $table->string('title');
        $table->text('content')->nullable();
        $table->string('url')->nullable();
        $table->integer('score')->default(0);
        $table->integer('comment_count')->default(0);
        $table->timestamp('published_at')->nullable();
        $table->timestamps();
        $table->softDeletes();
        
        $table->index(['topic_id', 'score']);
        $table->index('published_at');
    });
}

public function down(): void
{
    Schema::dropIfExists('posts');
}
```

### Routes

#### Naming
```php
// web.php
Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');

// api.php
Route::apiResource('posts', PostController::class);
```

#### Route Organization
```php
// Group by middleware
Route::middleware('auth')->group(function () {
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
});

// Group by prefix
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index']);
});
```

### Services

#### Naming
```php
// Resource name + Service
PostService
UserService
NotificationService
```

#### Service Structure
```php
class PostService
{
    public function __construct(
        private PostRepository $postRepository,
        private VoteService $voteService
    ) {}

    public function createPost(array $data): Post
    {
        DB::beginTransaction();
        
        try {
            $post = $this->postRepository->create($data);
            
            event(new PostCreated($post));
            
            DB::commit();
            
            return $post;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getHomeFeed(string $sort = 'hot'): Collection
    {
        return Cache::remember(
            "feed:{$sort}",
            now()->addMinutes(5),
            fn() => $this->postRepository->getFeed($sort)
        );
    }
}
```

### Repositories

#### Naming
```php
// Interface: Resource + RepositoryInterface
PostRepositoryInterface

// Implementation: Resource + Repository
PostRepository
```

#### Repository Structure
```php
// Interface
interface PostRepositoryInterface
{
    public function find(int $id): ?Post;
    public function create(array $data): Post;
    public function update(Post $post, array $data): Post;
    public function delete(Post $post): bool;
}

// Implementation
class PostRepository implements PostRepositoryInterface
{
    public function find(int $id): ?Post
    {
        return Post::find($id);
    }

    public function create(array $data): Post
    {
        return Post::create($data);
    }

    public function update(Post $post, array $data): Post
    {
        $post->update($data);
        return $post->fresh();
    }

    public function delete(Post $post): bool
    {
        return $post->delete();
    }

    public function getFeed(string $sort = 'hot'): Collection
    {
        $query = Post::with(['user', 'topic'])
            ->published();

        return match($sort) {
            'hot' => $query->orderByDesc('score')->get(),
            'new' => $query->latest()->get(),
            'top' => $query->orderByDesc('score')->get(),
            default => $query->latest()->get(),
        };
    }
}
```

### Form Requests

#### Naming
```php
// Action + Resource + Request
StorePostRequest
UpdatePostRequest
```

#### Request Structure
```php
class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:10000'],
            'url' => ['nullable', 'url'],
            'topic_id' => ['required', 'exists:topics,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Post title is required',
            'topic_id.exists' => 'Selected topic does not exist',
        ];
    }
}
```

### Actions

#### Naming
```php
// Verb + Resource + Action
CreatePostAction
UpdateVoteScoreAction
```

#### Action Structure
```php
class CreatePostAction
{
    public function execute(array $data): Post
    {
        $post = Post::create([
            'title' => $data['title'],
            'content' => $data['content'] ?? null,
            'url' => $data['url'] ?? null,
            'user_id' => $data['user_id'],
            'topic_id' => $data['topic_id'],
        ]);

        return $post;
    }
}
```

### Events & Listeners

#### Naming
```php
// Events: PastTense
PostCreated
CommentPosted
UserRegistered

// Listeners: Descriptive action
NotifyFollowers
UpdateFeedCache
SendWelcomeEmail
```

#### Event Structure
```php
class PostCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Post $post
    ) {}
}
```

#### Listener Structure
```php
class NotifyFollowers
{
    public function handle(PostCreated $event): void
    {
        $followers = $event->post->user->followers;

        foreach ($followers as $follower) {
            $follower->notify(new NewPostNotification($event->post));
        }
    }
}
```

### Jobs

#### Naming
```php
// Descriptive action + Job
ProcessPostJob
SendNotificationJob
```

#### Job Structure
```php
class ProcessPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Post $post
    ) {}

    public function handle(): void
    {
        // Process post
    }

    public function failed(\Throwable $exception): void
    {
        // Handle failure
    }
}
```

## Database Standards

### Table Naming
```
// Plural, snake_case
posts
users
comments
post_votes
```

### Column Naming
```
// snake_case
user_id
created_at
is_published
comment_count
```

### Foreign Keys
```
// {singular_table}_id
user_id
post_id
topic_id
```

### Pivot Tables
```
// Alphabetical order, singular
post_topic
topic_user
```

### Indexes
```
// Descriptive
{table}_{column}_index
posts_score_index
posts_user_id_foreign
```

## Blade Standards

### File Naming
```
// kebab-case
posts/index.blade.php
posts/show.blade.php
components/post-card.blade.php
```

### Blade Structure
```blade
@extends('layouts.app')

@section('title', 'Posts')

@section('content')
    <div class="container">
        <h1>Posts</h1>
        
        @foreach($posts as $post)
            <x-post-card :post="$post" />
        @endforeach
        
        {{ $posts->links() }}
    </div>
@endsection

@push('scripts')
    <script>
        // Page-specific scripts
    </script>
@endpush
```

### Components
```blade
{{-- components/post-card.blade.php --}}
@props(['post'])

<div class="post-card">
    <h2>{{ $post->title }}</h2>
    <p>{{ $post->excerpt }}</p>
    <div class="meta">
        <span>{{ $post->user->name }}</span>
        <span>{{ $post->created_at->diffForHumans() }}</span>
    </div>
</div>
```

## Testing Standards

### Test Naming
```php
// test_{method}_{scenario}_{expected_result}
test_create_post_with_valid_data_stores_post()
test_vote_post_without_authentication_fails()
```

### Test Structure
```php
class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_post(): void
    {
        // Arrange
        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        
        // Act
        $response = $this->actingAs($user)->post('/posts', [
            'title' => 'Test Post',
            'content' => 'Test content',
            'topic_id' => $topic->id,
        ]);
        
        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
            'user_id' => $user->id,
        ]);
    }
}
```

## Code Quality

### Static Analysis
- PHPStan (Level 5+)
- Laravel Pint for code style

### Code Review Checklist
- [ ] Follows naming conventions
- [ ] Proper type declarations
- [ ] No N+1 queries
- [ ] Proper error handling
- [ ] Security considerations
- [ ] Tests included
- [ ] Documentation updated

### Performance Checklist
- [ ] Eager loading relationships
- [ ] Proper indexing
- [ ] Caching strategy
- [ ] Query optimization
- [ ] Asset optimization

## Security Standards

### Input Validation
```php
// Always validate user input
$validated = $request->validate([
    'title' => 'required|string|max:255',
]);
```

### Authorization
```php
// Always check authorization
$this->authorize('update', $post);
```

### Mass Assignment Protection
```php
// Use $fillable or $guarded
protected $fillable = ['title', 'content'];
```

### XSS Prevention
```blade
{{-- Always escape output --}}
{{ $post->title }}

{{-- Use {!! !!} only for trusted content --}}
{!! $post->sanitized_html !!}
```

### SQL Injection Prevention
```php
// Use query builder or Eloquent
Post::where('user_id', $userId)->get();

// Never concatenate SQL
// BAD: DB::select("SELECT * FROM posts WHERE id = " . $id);
```

## Git Standards

### Commit Messages
```
feat: add voting system
fix: resolve N+1 query in feed
refactor: extract post creation logic to service
docs: update API documentation
test: add tests for comment system
chore: update dependencies
```

### Branch Naming
```
feature/voting-system
fix/n-plus-one-feed
refactor/post-service
```

## Documentation Standards

### Code Comments
```php
// Only when necessary to explain WHY, not WHAT
// Calculate hot score using Reddit algorithm
$hotScore = $this->calculateHotScore($post);
```

### API Documentation
```php
/**
 * @api {get} /api/v1/posts Get Posts
 * @apiName GetPosts
 * @apiGroup Posts
 * @apiParam {String} [sort=hot] Sort method (hot, new, top)
 * @apiSuccess {Object[]} posts List of posts
 */
```

---
**Last Updated**: 2026-08-05  
**Document Owner**: Lead Developer  
**Review Cycle**: Quarterly
