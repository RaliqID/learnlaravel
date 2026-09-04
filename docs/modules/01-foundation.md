# Module 01: Project Foundation

## Module Information
- **Module ID**: 01
- **Module Name**: Project Foundation
- **Status**: 📋 Planned
- **Priority**: CRITICAL
- **Dependencies**: None
- **Estimated Time**: 2-3 sessions
- **Phase**: Phase 1 - Foundation

---

## Module Overview

### Purpose
Membangun fondasi teknis project LaraNews dengan setup Laravel yang solid, terstruktur, dan siap untuk dikembangkan menjadi platform besar. Module ini adalah pondasi dari seluruh sistem.

### Scope
Module ini mencakup:
- Laravel installation dan configuration
- Environment setup (development, staging, production)
- Database connection setup
- Redis connection setup
- Directory structure standardization
- Core middleware configuration
- Error handling dan logging setup
- Helper utilities dan service providers
- Base testing infrastructure
- Development tools setup

### Actors
- **System Administrator**: Setup server dan environment
- **Developer**: Develop features di atas foundation ini
- **DevOps Engineer**: Deploy dan maintain infrastructure

---

## User Flow

### Developer Workflow
```
1. Clone repository
   ↓
2. Copy .env.example to .env
   ↓
3. Configure database credentials
   ↓
4. Configure Redis credentials
   ↓
5. Run composer install
   ↓
6. Generate application key
   ↓
7. Run migrations
   ↓
8. Run seeders (optional)
   ↓
9. Start development server
   ↓
10. Access application
```

### Deployment Workflow
```
1. Pull latest code
   ↓
2. Run composer install --optimize-autoloader --no-dev
   ↓
3. Run migrations
   ↓
4. Clear and cache config
   ↓
5. Clear and cache routes
   ↓
6. Clear and cache views
   ↓
7. Restart queue workers
   ↓
8. Restart PHP-FPM/Application
```

---

## Functional Requirements

### FR-F001: Laravel Installation
**Description**: Install Laravel 11.x dengan konfigurasi optimal untuk production-ready application.

**Acceptance Criteria**:
- [ ] Laravel 11.x successfully installed
- [ ] All required dependencies installed via Composer
- [ ] Application key generated
- [ ] Storage directories have correct permissions
- [ ] Public directory configured for web server

**Priority**: Must Have

**Technical Details**:
```bash
composer create-project laravel/laravel laranews "11.*"
php artisan key:generate
chmod -R 775 storage bootstrap/cache
```

---

### FR-F002: Environment Configuration
**Description**: Setup environment configuration untuk multiple environments (local, staging, production).

**Acceptance Criteria**:
- [ ] .env.example file documented dengan semua variables
- [ ] Environment-specific configuration separated
- [ ] Sensitive data never committed to repository
- [ ] Environment detection working correctly
- [ ] Config caching working in production

**Priority**: Must Have

**Environment Variables Required**:
```env
# Application
APP_NAME=LaraNews
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laranews
DB_USERNAME=root
DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@laranews.com
MAIL_FROM_NAME="${APP_NAME}"

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

---

### FR-F003: Database Connection Setup
**Description**: Configure MySQL database connection dengan connection pooling dan optimal settings.

**Acceptance Criteria**:
- [ ] MySQL connection configured in config/database.php
- [ ] Connection pooling enabled
- [ ] Read/write splitting prepared (optional for future)
- [ ] Timezone set to UTC
- [ ] Character set utf8mb4
- [ ] Collation utf8mb4_unicode_ci
- [ ] Connection successful and tested

**Priority**: Must Have

**Configuration**:
```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => 'InnoDB',
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
    ]) : [],
],
```

---

### FR-F004: Redis Connection Setup
**Description**: Configure Redis untuk cache, session, dan queue management.

**Acceptance Criteria**:
- [ ] Redis connection configured
- [ ] Cache driver set to Redis
- [ ] Session driver set to Redis
- [ ] Queue driver set to Redis
- [ ] Redis connection tested
- [ ] Redis persistence configured

**Priority**: Must Have

**Configuration**:
```php
// config/cache.php
'default' => env('CACHE_DRIVER', 'redis'),

// config/session.php
'driver' => env('SESSION_DRIVER', 'redis'),

// config/queue.php
'default' => env('QUEUE_CONNECTION', 'redis'),
```

---

### FR-F005: Directory Structure Standardization
**Description**: Create standardized directory structure sesuai architecture.md.

**Acceptance Criteria**:
- [ ] All directories created as per coding-standard.md
- [ ] .gitkeep files in empty directories
- [ ] Namespace autoloading configured
- [ ] PSR-4 autoloading working
- [ ] Directory structure documented

**Priority**: Must Have

**Directories to Create**:
```
app/
├── Actions/
├── DTOs/
├── Enums/
├── Repositories/
│   ├── Contracts/
│   └── Eloquent/
├── Services/
├── Traits/
└── (existing Laravel directories)
```

---

### FR-F006: Core Middleware Configuration
**Description**: Setup dan configure middleware yang akan digunakan di seluruh aplikasi.

**Acceptance Criteria**:
- [ ] Default Laravel middleware configured
- [ ] Custom middleware directory prepared
- [ ] Middleware groups configured (web, api)
- [ ] Middleware priority set
- [ ] Middleware aliases registered

**Priority**: Must Have

**Middleware Configuration**:
```php
// app/Http/Kernel.php or bootstrap/app.php (Laravel 11)

// Web middleware group
'web' => [
    \App\Http\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \App\Http\Middleware\VerifyCsrfToken::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],

// API middleware group
'api' => [
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

---

### FR-F007: Error Handling Setup
**Description**: Configure comprehensive error handling dan exception management.

**Acceptance Criteria**:
- [ ] Custom error pages (404, 500, 403, 503)
- [ ] Exception handler configured
- [ ] Error logging configured
- [ ] Error reporting configured
- [ ] Debug mode controlled by environment
- [ ] Sensitive data not exposed in errors

**Priority**: Must Have

**Custom Error Pages**:
```
resources/views/errors/
├── 404.blade.php
├── 403.blade.php
├── 500.blade.php
└── 503.blade.php
```

---

### FR-F008: Logging Configuration
**Description**: Setup comprehensive logging strategy untuk monitoring dan debugging.

**Acceptance Criteria**:
- [ ] Multiple log channels configured (single, daily, stack)
- [ ] Log rotation configured
- [ ] Log levels properly set per environment
- [ ] Separate logs for different concerns (errors, queries, auth)
- [ ] Log format standardized
- [ ] Sensitive data filtered from logs

**Priority**: Must Have

**Log Channels**:
```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'slack'],
        'ignore_exceptions' => false,
    ],
    
    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
    ],
    
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],
    
    'query' => [
        'driver' => 'daily',
        'path' => storage_path('logs/query.log'),
        'level' => 'debug',
        'days' => 7,
    ],
],
```

---

### FR-F009: Service Provider Setup
**Description**: Create dan register custom service providers untuk dependency injection.

**Acceptance Criteria**:
- [ ] RepositoryServiceProvider created
- [ ] AppServiceProvider configured
- [ ] Service providers registered in config
- [ ] Singleton bindings configured
- [ ] Interface to implementation bindings ready

**Priority**: Must Have

**Service Providers to Create**:
```
app/Providers/
├── AppServiceProvider.php
├── AuthServiceProvider.php
├── EventServiceProvider.php
├── RouteServiceProvider.php
└── RepositoryServiceProvider.php (new)
```

---

### FR-F010: Helper Utilities Setup
**Description**: Create common helper functions dan utilities untuk reusability.

**Acceptance Criteria**:
- [ ] helpers.php file created
- [ ] Common functions defined
- [ ] Helpers autoloaded in composer.json
- [ ] Helpers documented
- [ ] Helpers tested

**Priority**: Should Have

**Helper Functions**:
```php
// app/Helpers/helpers.php

/**
 * Format number with K, M, B suffixes
 */
function format_number($number) {
    // Implementation
}

/**
 * Generate slug from string
 */
function generate_slug($string) {
    // Implementation
}

/**
 * Calculate time ago
 */
function time_ago($timestamp) {
    // Implementation
}

/**
 * Sanitize HTML content
 */
function sanitize_html($content) {
    // Implementation
}
```

**Composer Autoload**:
```json
"autoload": {
    "files": [
        "app/Helpers/helpers.php"
    ]
}
```

---

### FR-F011: Base Testing Infrastructure
**Description**: Setup testing infrastructure dengan PHPUnit dan Laravel testing utilities.

**Acceptance Criteria**:
- [ ] PHPUnit configured
- [ ] Testing database configured
- [ ] Base test cases created (TestCase, FeatureTest, UnitTest)
- [ ] Testing traits configured
- [ ] Test coverage threshold set
- [ ] CI-ready test configuration

**Priority**: Must Have

**Test Configuration**:
```xml
<!-- phpunit.xml -->
<phpunit>
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
    </php>
</phpunit>
```

---

### FR-F012: Development Tools Setup
**Description**: Setup development tools untuk code quality dan productivity.

**Acceptance Criteria**:
- [ ] Laravel Pint installed dan configured
- [ ] Laravel Debugbar installed (dev only)
- [ ] Laravel Telescope installed (dev only)
- [ ] PHPStan or Larastan configured
- [ ] Git hooks prepared (optional)
- [ ] IDE helper configured

**Priority**: Should Have

**Development Dependencies**:
```json
"require-dev": {
    "laravel/pint": "^1.0",
    "barryvdh/laravel-debugbar": "^3.9",
    "laravel/telescope": "^4.0",
    "larastan/larastan": "^2.0",
    "barryvdh/laravel-ide-helper": "^2.13"
}
```

---

### FR-F013: Base Routes Configuration
**Description**: Setup route structure dan organization untuk web dan API.

**Acceptance Criteria**:
- [ ] Route files organized (web.php, api.php)
- [ ] Route model binding configured
- [ ] API prefix configured (/api/v1)
- [ ] Route caching working
- [ ] Rate limiting configured

**Priority**: Must Have

**Route Configuration**:
```php
// routes/web.php
Route::get('/', function () {
    return view('welcome');
});

// routes/api.php
Route::prefix('v1')->group(function () {
    // API routes here
});
```

---

### FR-F014: Asset Compilation Setup
**Description**: Setup Vite untuk asset compilation (CSS, JS).

**Acceptance Criteria**:
- [ ] Vite configured
- [ ] Tailwind CSS installed
- [ ] Alpine.js installed
- [ ] Asset compilation working (npm run dev)
- [ ] Production build working (npm run build)
- [ ] Hot reload working in development

**Priority**: Must Have

**Frontend Dependencies**:
```json
{
    "devDependencies": {
        "@tailwindcss/forms": "^0.5.7",
        "alpinejs": "^3.13.3",
        "autoprefixer": "^10.4.16",
        "axios": "^1.6.2",
        "laravel-vite-plugin": "^1.0.0",
        "postcss": "^8.4.32",
        "tailwindcss": "^3.3.6",
        "vite": "^5.0.8"
    }
}
```

---

### FR-F015: Configuration Caching
**Description**: Implement configuration caching untuk production performance.

**Acceptance Criteria**:
- [ ] Config cache command working
- [ ] Route cache command working
- [ ] View cache command working
- [ ] Event cache command working
- [ ] Optimization command working
- [ ] Cache clear command working

**Priority**: Must Have

**Artisan Commands**:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
php artisan optimize:clear
```

---

## Business Rules

### BR-F001: Environment Separation
Production, staging, dan local environments harus strictly separated dengan configuration berbeda.

### BR-F002: Debug Mode
Debug mode HANYA boleh enabled di local environment, TIDAK di production.

### BR-F003: Database Credentials
Database credentials TIDAK BOLEH di-commit ke repository dalam bentuk apapun.

### BR-F004: Error Exposure
Stack traces dan sensitive error information TIDAK BOLEH exposed ke end users di production.

### BR-F005: Log Retention
Application logs harus di-retain minimal 30 hari untuk audit purposes.

### BR-F006: Code Style
Semua code HARUS follow PSR-12 dan coding-standard.md yang sudah didefinisikan.

### BR-F007: Testing Requirement
Minimum 70% code coverage untuk critical paths sebelum deployment.

---

## Validation Rules

### VR-F001: Environment Variables
```php
'APP_NAME' => 'required|string|max:255',
'APP_ENV' => 'required|in:local,staging,production',
'APP_KEY' => 'required|string|size:32',
'APP_URL' => 'required|url',
'DB_CONNECTION' => 'required|in:mysql,pgsql,sqlite',
'DB_HOST' => 'required_if:DB_CONNECTION,mysql|ip',
'DB_PORT' => 'required_if:DB_CONNECTION,mysql|integer|between:1,65535',
'DB_DATABASE' => 'required|string',
'REDIS_HOST' => 'required|ip',
'REDIS_PORT' => 'required|integer|between:1,65535',
```

### VR-F002: PHP Version
```
PHP >= 8.2
```

### VR-F003: Required PHP Extensions
```
- OpenSSL
- PDO
- Mbstring
- Tokenizer
- XML
- Ctype
- JSON
- BCMath
- Redis
```

### VR-F004: Directory Permissions
```
storage/ => 775
bootstrap/cache/ => 775
```

---

## Database Behaviour

### DB-F001: Connection
- Default connection: MySQL
- Character set: utf8mb4
- Collation: utf8mb4_unicode_ci
- Timezone: UTC
- Strict mode: Enabled

### DB-F002: Migrations
- Migration table: migrations
- Migrations must be reversible (up/down methods)
- Timestamp-based naming

### DB-F003: Connection Pooling
- Max connections: 100 (configurable)
- Idle timeout: 60 seconds
- Connection retry: 3 attempts

---

## API Behaviour

### API-F001: Base URL
```
Development: http://localhost:8000/api/v1
Production: https://api.laranews.com/v1
```

### API-F002: Health Check Endpoint
```http
GET /api/health

Response: 200 OK
{
    "status": "ok",
    "timestamp": "2026-08-05T13:29:16Z",
    "database": "connected",
    "cache": "connected",
    "queue": "connected"
}
```

### API-F003: Version Endpoint
```http
GET /api/version

Response: 200 OK
{
    "version": "1.0.0",
    "laravel": "11.x",
    "php": "8.2.x"
}
```

---

## Permission & Authorization

### Module-Level Permissions
No specific permissions for foundation module.

Foundation setup accessible only by:
- System Administrator
- DevOps Engineer
- Senior Developer

---

## Security

### SEC-F001: Environment Security
- `.env` file NEVER committed to repository
- `.env.example` contains NO sensitive data
- Production credentials stored in secure vault

### SEC-F002: Debug Mode Security
- `APP_DEBUG=false` in production
- Custom error pages, no stack traces exposed
- Error details logged, not displayed

### SEC-F003: CSRF Protection
- CSRF middleware enabled on web routes
- CSRF token included in all forms
- API routes use Sanctum token authentication

### SEC-F004: HTTPS Enforcement
- Force HTTPS in production via middleware
- HSTS headers configured
- Secure cookies enabled

### SEC-F005: Security Headers
```php
// Middleware: app/Http/Middleware/SecurityHeaders.php
'X-Frame-Options' => 'SAMEORIGIN',
'X-Content-Type-Options' => 'nosniff',
'X-XSS-Protection' => '1; mode=block',
'Referrer-Policy' => 'strict-origin-when-cross-origin',
'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
```

---

## Performance

### PERF-F001: Configuration Caching
All config files cached in production for performance.

Target: Config load time < 1ms

### PERF-F002: Route Caching
All routes cached in production.

Target: Route resolution < 1ms

### PERF-F003: Autoloader Optimization
Composer autoloader optimized for production.

```bash
composer install --optimize-autoloader --no-dev
```

### PERF-F004: OPcache
PHP OPcache enabled in production.

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

### PERF-F005: Redis Connection
Redis connection persistent untuk performance.

Max connections: 100

---

## Accessibility

### ACC-F001: Error Messages
Error messages harus clear dan actionable, tidak hanya technical jargon.

### ACC-F002: Logging
Logs harus structured dan searchable untuk debugging efficiency.

### ACC-F003: Documentation
Semua configuration options documented di .env.example.

---

## Scalability

### SCAL-F001: Stateless Application
Application servers stateless, session di Redis untuk horizontal scaling.

### SCAL-F002: Database Connection Pooling
Connection pooling configured untuk handle multiple concurrent requests.

### SCAL-F003: Queue System
Queue system ready untuk offload heavy processing.

### SCAL-F004: Cache Layer
Redis cache layer ready untuk reduce database load.

### SCAL-F005: Asset Delivery
Assets compiled dan ready untuk CDN delivery.

---

## Edge Cases

### EC-F001: Missing Environment File
**Scenario**: .env file tidak ada saat application start.

**Handling**:
- Application throws clear error message
- Points to .env.example
- Provides setup instructions

### EC-F002: Database Connection Failed
**Scenario**: Database tidak reachable saat application start.

**Handling**:
- Application displays maintenance mode
- Error logged with connection details
- Retry mechanism dengan exponential backoff

### EC-F003: Redis Connection Failed
**Scenario**: Redis tidak reachable.

**Handling**:
- Fallback ke array/file cache driver
- Warning logged
- Application tetap berjalan dengan degraded performance

### EC-F004: Invalid APP_KEY
**Scenario**: APP_KEY tidak di-set atau invalid.

**Handling**:
- Application refuses to start
- Clear error message
- Instructions to run `php artisan key:generate`

### EC-F005: Permission Issues
**Scenario**: storage/ atau bootstrap/cache/ tidak writable.

**Handling**:
- Application throws permission error
- Error message includes directory path
- Instructions untuk fix permissions

### EC-F006: Port Already in Use
**Scenario**: Port 8000 already in use saat `php artisan serve`.

**Handling**:
- Error message with alternative port suggestion
- Developer can specify custom port

### EC-F007: Composer Dependencies Missing
**Scenario**: vendor/ directory tidak ada.

**Handling**:
- Application throws autoloader error
- Error message instructs to run `composer install`

---

## Error States

### ERR-F001: Configuration Error
```json
{
    "error": "ConfigurationException",
    "message": "Invalid configuration detected",
    "details": "APP_KEY is not set",
    "action": "Run 'php artisan key:generate'"
}
```

### ERR-F002: Database Connection Error
```json
{
    "error": "DatabaseConnectionException",
    "message": "Could not connect to database",
    "details": "Connection refused at 127.0.0.1:3306",
    "action": "Check database credentials and ensure MySQL is running"
}
```

### ERR-F003: Cache Connection Error
```json
{
    "error": "CacheConnectionException",
    "message": "Could not connect to Redis",
    "details": "Connection refused at 127.0.0.1:6379",
    "action": "Ensure Redis is running"
}
```

### ERR-F004: File Permission Error
```json
{
    "error": "FilePermissionException",
    "message": "Cannot write to storage directory",
    "details": "Permission denied: storage/logs/laravel.log",
    "action": "Run 'chmod -R 775 storage bootstrap/cache'"
}
```

---

## Success States

### SUC-F001: Successful Installation
```
✓ Laravel 11.x installed successfully
✓ Dependencies installed
✓ Application key generated
✓ Database connection verified
✓ Redis connection verified
✓ Directory permissions correct
✓ Assets compiled successfully

Application ready at: http://localhost:8000
```

### SUC-F002: Successful Environment Setup
```
✓ Environment file created (.env)
✓ Database configured
✓ Cache configured
✓ Queue configured
✓ Mail configured
✓ Configuration cached

Environment: local
Debug Mode: enabled
```

### SUC-F003: Successful Health Check
```http
GET /api/health

200 OK
{
    "status": "healthy",
    "checks": {
        "database": "ok",
        "cache": "ok",
        "queue": "ok",
        "storage": "ok"
    },
    "timestamp": "2026-08-05T13:29:16Z"
}
```

---

## Dependencies

### External Dependencies
- **PHP**: >= 8.2
- **Composer**: >= 2.5
- **Node.js**: >= 18.x
- **NPM**: >= 9.x
- **MySQL**: >= 8.0
- **Redis**: >= 7.0

### Laravel Packages
- `laravel/framework`: ^11.0
- `laravel/sanctum`: ^4.0
- `laravel/tinker`: ^2.9

### Development Packages
- `laravel/pint`: ^1.0
- `laravel/sail`: ^1.26
- `mockery/mockery`: ^1.6
- `phpunit/phpunit`: ^10.5

### Frontend Dependencies
- `vite`: ^5.0
- `tailwindcss`: ^3.3
- `alpinejs`: ^3.13

### Module Dependencies
**None** - This is the foundation module.

---

## Future Improvements

### FI-F001: Docker Support
Add Docker and Laravel Sail support untuk consistent development environment.

**Priority**: High
**Estimated Time**: 1 session

### FI-F002: CI/CD Pipeline
Setup GitHub Actions atau GitLab CI untuk automated testing dan deployment.

**Priority**: High
**Estimated Time**: 2 sessions

### FI-F003: Monitoring Integration
Integrate dengan monitoring tools (Sentry, New Relic, etc.).

**Priority**: Medium
**Estimated Time**: 1 session

### FI-F004: Database Read Replicas
Configure read/write splitting untuk database scalability.

**Priority**: Low
**Estimated Time**: 1 session

### FI-F005: Multi-tenancy Support
Add multi-tenancy support untuk potential SaaS model.

**Priority**: Low
**Estimated Time**: 3-4 sessions

---

## Testing Requirements

### Unit Tests

#### Test: Configuration Loading
```php
public function test_configuration_loads_correctly()
{
    $this->assertEquals('LaraNews', config('app.name'));
    $this->assertEquals('redis', config('cache.default'));
}
```

#### Test: Database Connection
```php
public function test_database_connection_works()
{
    DB::connection()->getPdo();
    $this->assertTrue(true);
}
```

#### Test: Redis Connection
```php
public function test_redis_connection_works()
{
    Cache::driver('redis')->put('test', 'value', 10);
    $this->assertEquals('value', Cache::driver('redis')->get('test'));
}
```

#### Test: Helper Functions
```php
public function test_helper_functions_work()
{
    $this->assertEquals('1K', format_number(1000));
    $this->assertEquals('hello-world', generate_slug('Hello World'));
}
```

### Feature Tests

#### Test: Health Check Endpoint
```php
public function test_health_check_endpoint_returns_ok()
{
    $response = $this->get('/api/health');
    
    $response->assertStatus(200)
        ->assertJson([
            'status' => 'ok',
            'database' => 'connected',
            'cache' => 'connected',
        ]);
}
```

#### Test: Version Endpoint
```php
public function test_version_endpoint_returns_version()
{
    $response = $this->get('/api/version');
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'version',
            'laravel',
            'php'
        ]);
}
```

#### Test: Error Pages
```php
public function test_404_page_displays_correctly()
{
    $response = $this->get('/non-existent-page');
    
    $response->assertStatus(404)
        ->assertViewIs('errors.404');
}
```

### Integration Tests

#### Test: Cache and Database Integration
```php
public function test_cache_and_database_integration()
{
    // Create test record
    $user = User::factory()->create();
    
    // Cache the record
    Cache::put("user:{$user->id}", $user, 60);
    
    // Retrieve from cache
    $cached = Cache::get("user:{$user->id}");
    
    $this->assertEquals($user->id, $cached->id);
}
```

### Test Coverage Target
- **Overall**: >= 70%
- **Critical Paths**: >= 90%
- **Configuration**: 100%
- **Core Services**: >= 85%

---

## Module Summary

### Completion Checklist
- [ ] Laravel 11.x installed
- [ ] Environment configured (.env)
- [ ] Database connection setup and tested
- [ ] Redis connection setup and tested
- [ ] Directory structure created
- [ ] Middleware configured
- [ ] Error handling setup
- [ ] Logging configured
- [ ] Service providers created
- [ ] Helper utilities created
- [ ] Testing infrastructure setup
- [ ] Development tools installed
- [ ] Routes configured
- [ ] Assets compilation working
- [ ] Configuration caching tested
- [ ] Health check endpoint working
- [ ] All tests passing
- [ ] Documentation updated

### Definition of Done
- ✅ All functional requirements implemented
- ✅ All tests written and passing (>70% coverage)
- ✅ Code follows coding-standard.md
- ✅ Security checklist completed
- ✅ Performance benchmarks met
- ✅ Documentation complete
- ✅ Code reviewed and approved
- ✅ Deployed to staging and verified
- ✅ Session handover document updated

### Dependencies Verification
- ✅ PHP 8.2+ installed
- ✅ Composer 2.5+ installed
- ✅ Node.js 18+ installed
- ✅ MySQL 8.0+ running
- ✅ Redis 7.0+ running

### Next Module
**Module 02: Authentication**

After foundation is complete, proceed to Module 02 untuk implement user registration, login, dan email verification system.

---

## Change History
- **2026-08-05**: Initial module documentation created

---

**Document Owner**: Technical Lead  
**Last Updated**: 2026-08-05  
**Status**: 📋 Planned  
**Ready for Implementation**: ✅ Yes
