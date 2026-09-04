# API Standards

## API Overview
LaraNews menyediakan RESTful API untuk integrasi eksternal dan mobile applications. API menggunakan JSON format dan mengikuti best practices REST architecture.

## API Principles
1. **RESTful Design**: Resource-based URLs, HTTP methods
2. **Versioning**: URL-based versioning (`/api/v1/`)
3. **Consistency**: Consistent response format
4. **Security**: Token-based authentication (Sanctum)
5. **Rate Limiting**: Prevent abuse
6. **Documentation**: Comprehensive API docs
7. **Backward Compatibility**: Maintain compatibility within versions

## Base URL
```
Development: http://localhost:8000/api/v1
Production: https://api.laranews.com/v1
```

## Authentication

### Token-Based (Laravel Sanctum)
```http
POST /api/v1/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password123"
}

Response:
{
    "status": "success",
    "data": {
        "token": "1|abc123...",
        "user": {
            "id": 1,
            "username": "johndoe",
            "email": "user@example.com"
        }
    }
}
```

### Using Token
```http
GET /api/v1/posts
Authorization: Bearer {token}
```

## HTTP Methods

### Standard Methods
- `GET`: Retrieve resource(s)
- `POST`: Create new resource
- `PUT`: Full update resource
- `PATCH`: Partial update resource
- `DELETE`: Delete resource

### Examples
```http
GET    /api/v1/posts           # List posts
GET    /api/v1/posts/123       # Get specific post
POST   /api/v1/posts           # Create post
PUT    /api/v1/posts/123       # Full update post
PATCH  /api/v1/posts/123       # Partial update post
DELETE /api/v1/posts/123       # Delete post
```

## Response Format

### Success Response
```json
{
    "status": "success",
    "data": {
        "id": 123,
        "title": "Example Post"
    },
    "meta": {
        "timestamp": "2026-08-05T13:29:16Z"
    }
}
```

### Error Response
```json
{
    "status": "error",
    "message": "Resource not found",
    "errors": {
        "id": ["The specified post does not exist"]
    },
    "meta": {
        "timestamp": "2026-08-05T13:29:16Z"
    }
}
```

### Paginated Response
```json
{
    "status": "success",
    "data": [
        {"id": 1, "title": "Post 1"},
        {"id": 2, "title": "Post 2"}
    ],
    "meta": {
        "pagination": {
            "total": 100,
            "count": 20,
            "per_page": 20,
            "current_page": 1,
            "last_page": 5
        },
        "timestamp": "2026-08-05T13:29:16Z"
    }
}
```

## HTTP Status Codes

### Success Codes
- `200 OK`: Request succeeded
- `201 Created`: Resource created
- `204 No Content`: Success with no response body

### Client Error Codes
- `400 Bad Request`: Invalid request format
- `401 Unauthorized`: Authentication required
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation failed
- `429 Too Many Requests`: Rate limit exceeded

### Server Error Codes
- `500 Internal Server Error`: Server error
- `503 Service Unavailable`: Service temporarily unavailable

## Validation Errors

### Format
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "title": [
            "The title field is required.",
            "The title must be at least 10 characters."
        ],
        "topic_id": [
            "The selected topic is invalid."
        ]
    },
    "meta": {
        "timestamp": "2026-08-05T13:29:16Z"
    }
}
```

## Rate Limiting

### Limits
- **Authenticated Users**: 60 requests/minute
- **Unauthenticated Users**: 30 requests/minute
- **Admin Users**: 120 requests/minute

### Headers
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1659705600
```

### Rate Limit Exceeded Response
```json
{
    "status": "error",
    "message": "Too many requests. Please try again later.",
    "meta": {
        "retry_after": 60,
        "timestamp": "2026-08-05T13:29:16Z"
    }
}
```

## Pagination

### Query Parameters
```http
GET /api/v1/posts?page=2&per_page=20
```

### Parameters
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 20, max: 100)

## Filtering

### Query Parameters
```http
GET /api/v1/posts?topic_id=5&sort=hot&time=day
```

### Common Filters
- `topic_id`: Filter by topic
- `user_id`: Filter by user
- `sort`: Sort method (hot, new, top)
- `time`: Time range (hour, day, week, month, year, all)

## Sorting

### Query Parameters
```http
GET /api/v1/posts?sort=hot&order=desc
```

### Parameters
- `sort`: Field to sort by
- `order`: Sort direction (asc, desc)

## Searching

### Query Parameters
```http
GET /api/v1/posts?q=laravel&topics=1,2,3
```

### Parameters
- `q`: Search query
- `topics`: Topic IDs (comma-separated)

## Includes (Eager Loading)

### Query Parameters
```http
GET /api/v1/posts/123?include=user,topic,comments
```

### Available Includes
- `user`: Post author
- `topic`: Post topic
- `comments`: Post comments
- `votes`: Vote information

## API Endpoints

### Authentication
```http
POST   /api/v1/auth/register      # Register new user
POST   /api/v1/auth/login         # Login
POST   /api/v1/auth/logout        # Logout
POST   /api/v1/auth/refresh       # Refresh token
GET    /api/v1/auth/me            # Get current user
```

### Users
```http
GET    /api/v1/users              # List users
GET    /api/v1/users/{id}         # Get user
PUT    /api/v1/users/{id}         # Update user
DELETE /api/v1/users/{id}         # Delete user
GET    /api/v1/users/{id}/posts   # Get user posts
GET    /api/v1/users/{id}/comments # Get user comments
POST   /api/v1/users/{id}/follow  # Follow user
DELETE /api/v1/users/{id}/follow  # Unfollow user
```

### Topics
```http
GET    /api/v1/topics             # List topics
GET    /api/v1/topics/{id}        # Get topic
POST   /api/v1/topics             # Create topic (admin)
PUT    /api/v1/topics/{id}        # Update topic (admin)
DELETE /api/v1/topics/{id}        # Delete topic (admin)
POST   /api/v1/topics/{id}/subscribe   # Subscribe to topic
DELETE /api/v1/topics/{id}/subscribe   # Unsubscribe from topic
```

### Posts
```http
GET    /api/v1/posts              # List posts
GET    /api/v1/posts/{id}         # Get post
POST   /api/v1/posts              # Create post
PUT    /api/v1/posts/{id}         # Update post
DELETE /api/v1/posts/{id}         # Delete post
POST   /api/v1/posts/{id}/vote    # Vote on post
DELETE /api/v1/posts/{id}/vote    # Remove vote
POST   /api/v1/posts/{id}/bookmark # Bookmark post
DELETE /api/v1/posts/{id}/bookmark # Remove bookmark
```

### Comments
```http
GET    /api/v1/posts/{id}/comments      # List post comments
POST   /api/v1/posts/{id}/comments      # Create comment
GET    /api/v1/comments/{id}            # Get comment
PUT    /api/v1/comments/{id}            # Update comment
DELETE /api/v1/comments/{id}            # Delete comment
POST   /api/v1/comments/{id}/vote       # Vote on comment
DELETE /api/v1/comments/{id}/vote       # Remove vote
```

### Notifications
```http
GET    /api/v1/notifications            # List notifications
GET    /api/v1/notifications/{id}       # Get notification
PUT    /api/v1/notifications/{id}/read  # Mark as read
PUT    /api/v1/notifications/read-all   # Mark all as read
DELETE /api/v1/notifications/{id}       # Delete notification
```

### Search
```http
GET    /api/v1/search                   # Search all
GET    /api/v1/search/posts             # Search posts
GET    /api/v1/search/users             # Search users
GET    /api/v1/search/topics            # Search topics
```

### Bookmarks
```http
GET    /api/v1/bookmarks                # List user bookmarks
DELETE /api/v1/bookmarks/{id}           # Remove bookmark
```

### Reports
```http
POST   /api/v1/reports                  # Submit report
GET    /api/v1/reports                  # List reports (mod)
PUT    /api/v1/reports/{id}             # Update report (mod)
```

## Resource Representation

### User Resource
```json
{
    "id": 1,
    "username": "johndoe",
    "display_name": "John Doe",
    "bio": "Laravel enthusiast",
    "avatar": "https://example.com/avatars/1.jpg",
    "karma_score": 1250,
    "post_count": 45,
    "comment_count": 230,
    "created_at": "2026-01-15T10:30:00Z"
}
```

### Topic Resource
```json
{
    "id": 1,
    "name": "Laravel",
    "slug": "laravel",
    "description": "Laravel framework discussion",
    "icon": "https://example.com/icons/laravel.png",
    "subscriber_count": 5420,
    "post_count": 1230,
    "is_subscribed": true,
    "created_at": "2026-01-01T00:00:00Z"
}
```

### Post Resource
```json
{
    "id": 123,
    "title": "Getting Started with Laravel 11",
    "content": "This is a comprehensive guide...",
    "url": null,
    "post_type": "text",
    "vote_score": 45,
    "comment_count": 12,
    "view_count": 523,
    "is_pinned": false,
    "is_locked": false,
    "user": {
        "id": 1,
        "username": "johndoe"
    },
    "topic": {
        "id": 1,
        "name": "Laravel",
        "slug": "laravel"
    },
    "user_vote": 1,
    "is_bookmarked": false,
    "published_at": "2026-08-05T10:00:00Z",
    "created_at": "2026-08-05T10:00:00Z",
    "updated_at": "2026-08-05T12:30:00Z"
}
```

### Comment Resource
```json
{
    "id": 456,
    "content": "Great post! Thanks for sharing.",
    "vote_score": 12,
    "depth": 0,
    "reply_count": 3,
    "user": {
        "id": 2,
        "username": "janedoe"
    },
    "user_vote": null,
    "created_at": "2026-08-05T11:00:00Z",
    "updated_at": "2026-08-05T11:00:00Z",
    "edited_at": null
}
```

### Notification Resource
```json
{
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "type": "CommentReply",
    "data": {
        "post_id": 123,
        "comment_id": 456,
        "user": {
            "id": 2,
            "username": "janedoe"
        },
        "message": "janedoe replied to your comment"
    },
    "read_at": null,
    "created_at": "2026-08-05T12:00:00Z"
}
```

## Request Examples

### Create Post
```http
POST /api/v1/posts
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "Getting Started with Laravel 11",
    "content": "This is a comprehensive guide to Laravel 11...",
    "topic_id": 1,
    "post_type": "text"
}

Response: 201 Created
{
    "status": "success",
    "data": {
        "id": 123,
        "title": "Getting Started with Laravel 11",
        ...
    },
    "message": "Post created successfully"
}
```

### Vote on Post
```http
POST /api/v1/posts/123/vote
Authorization: Bearer {token}
Content-Type: application/json

{
    "vote_type": 1
}

Response: 200 OK
{
    "status": "success",
    "data": {
        "vote_score": 46,
        "user_vote": 1
    },
    "message": "Vote recorded"
}
```

### Get Posts with Filters
```http
GET /api/v1/posts?topic_id=1&sort=hot&page=1&per_page=20&include=user,topic
Authorization: Bearer {token}

Response: 200 OK
{
    "status": "success",
    "data": [
        {...},
        {...}
    ],
    "meta": {
        "pagination": {...}
    }
}
```

## Error Handling

### Not Found
```json
{
    "status": "error",
    "message": "Post not found",
    "meta": {
        "timestamp": "2026-08-05T13:29:16Z"
    }
}
```

### Unauthorized
```json
{
    "status": "error",
    "message": "Unauthenticated",
    "meta": {
        "timestamp": "2026-08-05T13:29:16Z"
    }
}
```

### Forbidden
```json
{
    "status": "error",
    "message": "You do not have permission to perform this action",
    "meta": {
        "timestamp": "2026-08-05T13:29:16Z"
    }
}
```

## Versioning

### URL Versioning
```
/api/v1/posts
/api/v2/posts (future)
```

### Version Support
- Current: v1
- Deprecated: None
- Sunset Policy: 6 months notice

## Security

### HTTPS Only
All API requests must use HTTPS in production.

### CORS
```php
Allowed Origins: Configure in config/cors.php
Allowed Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
Allowed Headers: Content-Type, Authorization, X-Requested-With
```

### Input Sanitization
All inputs are validated and sanitized before processing.

### SQL Injection Prevention
Using Eloquent ORM and parameterized queries.

### XSS Prevention
Output encoding for all user-generated content.

## Testing

### Test Endpoints
```
Staging: https://staging-api.laranews.com/v1
Testing: https://test-api.laranews.com/v1
```

### Postman Collection
Available at: `/docs/postman/laranews-api.json`

### OpenAPI Specification
Available at: `/docs/openapi.yaml`

## Webhook (Future Feature)

### Events
- post.created
- post.updated
- post.deleted
- comment.created
- user.registered

### Webhook Format
```json
{
    "event": "post.created",
    "data": {...},
    "timestamp": "2026-08-05T13:29:16Z"
}
```

## Best Practices

### For API Consumers
1. Always handle errors gracefully
2. Implement exponential backoff for retries
3. Cache responses when appropriate
4. Use pagination for large datasets
5. Include version in integration planning
6. Monitor rate limits
7. Use HTTPS only
8. Store tokens securely

### For API Developers
1. Follow RESTful principles
2. Maintain backward compatibility
3. Document all changes
4. Version breaking changes
5. Validate all inputs
6. Use proper HTTP status codes
7. Implement rate limiting
8. Monitor API usage

## Rate Limit Best Practices

### Handling Rate Limits
```javascript
if (response.status === 429) {
    const retryAfter = response.headers['X-RateLimit-Reset'];
    // Wait and retry
}
```

### Exponential Backoff
```
First retry: 1 second
Second retry: 2 seconds
Third retry: 4 seconds
...
```

---
**Last Updated**: 2026-08-05  
**Document Owner**: API Architect  
**Review Cycle**: Every API change
