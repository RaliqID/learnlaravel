# System Architecture

## Architecture Overview
LaraNews menggunakan arsitektur **Monolithic Modular** dengan Laravel sebagai core framework. Arsitektur ini dipilih untuk balance antara simplicity dan scalability.

## Architecture Principles
1. **Separation of Concerns**: Setiap layer memiliki tanggung jawab yang jelas
2. **Dependency Injection**: Loose coupling antar komponen
3. **Repository Pattern**: Abstraksi data access layer
4. **Service Layer**: Business logic terpisah dari controller
5. **Event-Driven**: Asynchronous processing untuk operasi berat
6. **Cache-First**: Strategi caching agresif untuk performa

## System Layers

### 1. Presentation Layer
**Responsibility**: Menampilkan data ke user dan menerima input

**Components**:
- **Controllers**: Handle HTTP requests
- **Views**: Blade templates untuk UI
- **Resources**: API response transformers
- **Requests**: Form validation
- **Middleware**: Request/response filtering

**Location**: `app/Http/`

### 2. Application Layer
**Responsibility**: Orchestrate business logic dan use cases

**Components**:
- **Services**: Business logic implementation
- **Actions**: Single-purpose operations
- **DTOs**: Data Transfer Objects
- **Validators**: Business rule validation

**Location**: `app/Services/`, `app/Actions/`

### 3. Domain Layer
**Responsibility**: Core business entities dan rules

**Components**:
- **Models**: Eloquent models (entities)
- **Enums**: Type-safe constants
- **Value Objects**: Immutable domain objects
- **Policies**: Authorization logic

**Location**: `app/Models/`, `app/Enums/`, `app/Policies/`

### 4. Infrastructure Layer
**Responsibility**: Technical implementations dan external integrations

**Components**:
- **Repositories**: Data access abstraction
- **Events**: Event definitions
- **Listeners**: Event handlers
- **Jobs**: Queue workers
- **Notifications**: Notification channels
- **Mail**: Email templates

**Location**: `app/Repositories/`, `app/Events/`, `app/Listeners/`, `app/Jobs/`

### 5. Data Layer
**Responsibility**: Data persistence dan retrieval

**Components**:
- **Migrations**: Database schema
- **Seeders**: Initial data
- **Factories**: Test data generation

**Location**: `database/`

## Directory Structure
```
app/
├── Actions/              # Single-purpose business operations
├── Console/              # CLI commands
├── DTOs/                 # Data Transfer Objects
├── Enums/                # Type-safe enumerations
├── Events/               # Event definitions
├── Exceptions/           # Custom exceptions
├── Helpers/              # Helper functions
├── Http/
│   ├── Controllers/      # HTTP controllers
│   ├── Middleware/       # HTTP middleware
│   ├── Requests/         # Form requests
│   └── Resources/        # API resources
├── Jobs/                 # Queue jobs
├── Listeners/            # Event listeners
├── Mail/                 # Email templates
├── Models/               # Eloquent models
├── Notifications/        # Notification classes
├── Policies/             # Authorization policies
├── Providers/            # Service providers
├── Repositories/         # Repository pattern
│   ├── Contracts/        # Repository interfaces
│   └── Eloquent/         # Eloquent implementations
├── Services/             # Business logic services
└── Traits/               # Reusable traits

config/                   # Configuration files
database/
├── factories/            # Model factories
├── migrations/           # Database migrations
└── seeders/              # Database seeders

public/                   # Public assets
resources/
├── css/                  # Stylesheets
├── js/                   # JavaScript
└── views/                # Blade templates
    ├── components/       # Reusable components
    ├── layouts/          # Layout templates
    └── pages/            # Page views

routes/
├── api.php               # API routes
├── web.php               # Web routes
└── channels.php          # Broadcast channels

storage/
├── app/                  # Application storage
├── framework/            # Framework storage
└── logs/                 # Log files

tests/
├── Feature/              # Feature tests
└── Unit/                 # Unit tests
```

## Design Patterns

### 1. Repository Pattern
Abstraksi untuk data access, memudahkan testing dan maintainability.

```
Interface: app/Repositories/Contracts/PostRepositoryInterface.php
Implementation: app/Repositories/Eloquent/PostRepository.php
Binding: app/Providers/RepositoryServiceProvider.php
```

### 2. Service Pattern
Business logic dikelompokkan dalam service classes.

```
Service: app/Services/PostService.php
Usage: Injected ke controller via constructor
```

### 3. Action Pattern
Single-purpose operations untuk complex business logic.

```
Action: app/Actions/Post/CreatePostAction.php
Usage: Invoked dari service atau controller
```

### 4. Strategy Pattern
Berbagai algoritma untuk use case yang sama (misal: feed sorting).

```
Interface: app/Services/Feed/FeedStrategyInterface.php
Strategies: HotStrategy, NewStrategy, TopStrategy
```

### 5. Observer Pattern
Event-driven architecture untuk loose coupling.

```
Event: app/Events/PostCreated.php
Listener: app/Listeners/NotifyFollowers.php
```

## Data Flow

### Request Flow (Web)
```
1. Browser Request
   ↓
2. Route (web.php)
   ↓
3. Middleware (authentication, validation)
   ↓
4. Controller (handle request)
   ↓
5. Form Request (validation)
   ↓
6. Service (business logic)
   ↓
7. Repository (data access)
   ↓
8. Model (Eloquent)
   ↓
9. Database
   ↓
10. Return response through layers
   ↓
11. View (Blade template)
   ↓
12. Browser Response
```

### Request Flow (API)
```
1. API Request
   ↓
2. Route (api.php)
   ↓
3. Middleware (sanctum, throttle)
   ↓
4. Controller (handle request)
   ↓
5. Form Request (validation)
   ↓
6. Service (business logic)
   ↓
7. Repository (data access)
   ↓
8. Model (Eloquent)
   ↓
9. Database
   ↓
10. Return response through layers
   ↓
11. Resource (transform data)
   ↓
12. JSON Response
```

### Event Flow
```
1. Action triggers event (PostCreated)
   ↓
2. Event dispatched
   ↓
3. Listeners execute (NotifyFollowers, UpdateFeed)
   ↓
4. Jobs queued for async processing
   ↓
5. Queue worker processes jobs
```

## Caching Strategy

### Cache Layers
1. **Application Cache (Redis)**
   - User sessions
   - Feed cache
   - Query cache
   - Rate limiting

2. **Database Query Cache**
   - Eloquent model cache
   - Expensive queries cache

3. **HTTP Cache**
   - Static assets
   - CDN cache

### Cache Keys Convention
```
{entity}:{id}:{attribute}
user:123:profile
post:456:votes
topic:789:posts
feed:hot:page:1
```

### Cache Invalidation
- Event-driven cache invalidation
- Time-based expiration
- Tag-based invalidation

## Queue System

### Queue Types
1. **High Priority**: Notifications, real-time updates
2. **Default**: General processing
3. **Low Priority**: Analytics, reports

### Queue Workers
- Separate workers per queue
- Supervisor for process management
- Failed job handling

## Security Architecture

### Authentication
- Session-based (Web)
- Token-based (API via Sanctum)
- Rate limiting per endpoint

### Authorization
- Policy-based authorization
- Role and permission system
- Owner-based access control

### Data Protection
- CSRF protection
- XSS prevention
- SQL injection prevention (Eloquent)
- Input sanitization
- Output encoding

## Database Architecture

### Database Design Principles
1. **Normalization**: 3NF for most tables
2. **Denormalization**: Strategic for performance (vote counts, etc.)
3. **Soft Deletes**: For user-generated content
4. **Timestamps**: All tables have created_at/updated_at
5. **Indexing**: Strategic indexes on foreign keys and search fields

### Database Connection
- Primary: MySQL (read/write)
- Replica: MySQL (read-only, optional)
- Connection pooling via Laravel

## API Architecture

### API Design
- RESTful principles
- Resource-based endpoints
- Consistent response format
- Versioning via URL (`/api/v1/`)

### API Response Format
```json
{
  "status": "success|error",
  "data": {},
  "message": "string",
  "meta": {
    "pagination": {}
  }
}
```

### API Rate Limiting
- Authenticated: 60 requests/minute
- Unauthenticated: 30 requests/minute
- Admin: 120 requests/minute

## Performance Architecture

### Performance Targets
- Page Load: < 200ms
- API Response: < 100ms
- Time to Interactive: < 1s
- Database Queries: < 50ms

### Performance Strategies
1. **Eager Loading**: Prevent N+1 queries
2. **Query Optimization**: Index optimization
3. **Caching**: Aggressive caching strategy
4. **Asset Optimization**: Minification, compression
5. **CDN**: Static asset delivery
6. **Lazy Loading**: Images and non-critical resources

## Scalability Architecture

### Horizontal Scaling
- Stateless application servers
- Session stored in Redis (not server)
- Queue workers can be scaled independently

### Vertical Scaling
- Database optimization
- Connection pooling
- Resource allocation

### Future Considerations
- Load balancer
- Database sharding
- Microservices extraction (optional)

## Monitoring & Logging

### Monitoring
- Application performance (APM)
- Database performance
- Queue monitoring
- Error tracking

### Logging
- Application logs (storage/logs)
- Query logs (slow queries)
- Error logs (exceptions)
- Access logs (web server)

### Log Levels
- DEBUG: Development only
- INFO: General information
- WARNING: Warning conditions
- ERROR: Error conditions
- CRITICAL: Critical conditions

## Development Environment

### Local Development
- Laravel Sail (Docker)
- Valet (macOS)
- Homestead (Vagrant)

### Development Tools
- Laravel Debugbar
- Laravel Telescope
- PHPUnit for testing
- Laravel Pint for code style

## Deployment Architecture

### Server Requirements
- PHP 8.2+
- MySQL 8.0+
- Redis 7.0+
- Nginx/Apache
- Supervisor (queue workers)

### Deployment Strategy
- Zero-downtime deployment
- Database migration strategy
- Asset compilation
- Cache clearing
- Queue restart

## Technology Decisions

### Why Laravel?
- Mature ecosystem
- Built-in authentication
- Eloquent ORM
- Queue system
- Event system
- Testing support

### Why MySQL?
- Relational data model
- ACID compliance
- Wide adoption
- Good performance
- Laravel support

### Why Redis?
- Fast in-memory storage
- Session storage
- Cache storage
- Queue driver
- Pub/sub support

### Why Blade?
- Laravel native
- Simple syntax
- Component system
- Good performance
- Easy to learn

---
**Last Updated**: 2026-08-05  
**Document Owner**: System Architect  
**Review Cycle**: Every architectural change
