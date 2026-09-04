# Database Design

## Database Overview
LaraNews menggunakan MySQL 8.0+ sebagai primary database dengan Redis sebagai cache layer. Database dirancang dengan prinsip normalization namun dengan strategic denormalization untuk performance.

## Database Principles
1. **Normalization**: Target 3NF untuk data integrity
2. **Strategic Denormalization**: Counter fields untuk performance
3. **Soft Deletes**: User-generated content menggunakan soft deletes
4. **Timestamps**: Semua tabel memiliki created_at dan updated_at
5. **Indexing**: Strategic indexes pada foreign keys dan query fields
6. **Constraints**: Foreign key constraints untuk referential integrity

## Entity Relationship Overview

```
Users ──┬── Posts ──┬── Comments ──── CommentVotes
        │           │
        │           └── PostVotes
        │
        ├── Follows (self-referencing)
        ├── Bookmarks
        ├── Notifications
        └── TopicSubscriptions

Topics ──┬── Posts
         └── TopicSubscriptions

Reports ──┬── Posts
          ├── Comments
          └── Users
```

## Core Tables

### users
User accounts dan profile information.

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    
    -- Profile
    display_name VARCHAR(100) NULL,
    bio TEXT NULL,
    avatar VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    location VARCHAR(100) NULL,
    
    -- Stats (denormalized)
    karma_score INT DEFAULT 0,
    post_count INT DEFAULT 0,
    comment_count INT DEFAULT 0,
    
    -- Settings
    show_email BOOLEAN DEFAULT FALSE,
    email_notifications BOOLEAN DEFAULT TRUE,
    
    -- Status
    is_banned BOOLEAN DEFAULT FALSE,
    banned_until TIMESTAMP NULL,
    banned_reason TEXT NULL,
    
    -- Roles
    role ENUM('user', 'moderator', 'admin') DEFAULT 'user',
    
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_karma_score (karma_score),
    INDEX idx_role (role),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Business Rules**:
- Username: 3-50 characters, alphanumeric + underscore/dash
- Email: Valid email format, unique
- Karma: Calculated from votes received
- Soft delete: Preserve content when user deleted

### topics
Categories/communities untuk organizing posts.

```sql
CREATE TABLE topics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT NULL,
    icon VARCHAR(255) NULL,
    banner VARCHAR(255) NULL,
    
    -- Stats (denormalized)
    subscriber_count INT DEFAULT 0,
    post_count INT DEFAULT 0,
    
    -- Settings
    is_active BOOLEAN DEFAULT TRUE,
    is_private BOOLEAN DEFAULT FALSE,
    requires_approval BOOLEAN DEFAULT FALSE,
    
    -- Moderation
    rules TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_slug (slug),
    INDEX idx_subscriber_count (subscriber_count),
    INDEX idx_is_active (is_active),
    FULLTEXT idx_name_description (name, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Business Rules**:
- Name: 3-100 characters, unique
- Slug: Auto-generated from name, URL-safe
- Soft delete: Topics can be archived

### posts
User-submitted content (links, text, images).

```sql
CREATE TABLE posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    topic_id BIGINT UNSIGNED NOT NULL,
    
    -- Content
    title VARCHAR(300) NOT NULL,
    content TEXT NULL,
    url VARCHAR(2048) NULL,
    post_type ENUM('link', 'text', 'image') NOT NULL,
    
    -- Media
    image_url VARCHAR(255) NULL,
    thumbnail_url VARCHAR(255) NULL,
    
    -- Engagement (denormalized)
    vote_score INT DEFAULT 0,
    upvote_count INT DEFAULT 0,
    downvote_count INT DEFAULT 0,
    comment_count INT DEFAULT 0,
    view_count INT DEFAULT 0,
    bookmark_count INT DEFAULT 0,
    
    -- Ranking
    hot_score DECIMAL(10,4) DEFAULT 0,
    controversy_score DECIMAL(10,4) DEFAULT 0,
    
    -- Status
    is_pinned BOOLEAN DEFAULT FALSE,
    is_locked BOOLEAN DEFAULT FALSE,
    is_nsfw BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT TRUE,
    
    -- Timestamps
    published_at TIMESTAMP NULL,
    edited_at TIMESTAMP NULL,
    last_activity_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_topic_id (topic_id),
    INDEX idx_post_type (post_type),
    INDEX idx_vote_score (vote_score),
    INDEX idx_hot_score (hot_score),
    INDEX idx_published_at (published_at),
    INDEX idx_last_activity_at (last_activity_at),
    INDEX idx_topic_score (topic_id, vote_score),
    INDEX idx_topic_hot (topic_id, hot_score),
    FULLTEXT idx_title_content (title, content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Business Rules**:
- Title: 10-300 characters, required
- Content OR URL required (depends on post_type)
- Vote score: upvote_count - downvote_count
- Hot score: Calculated using time-decay algorithm
- Soft delete: Preserve for moderation history

### comments
Threaded comments on posts.

```sql
CREATE TABLE comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    
    -- Content
    content TEXT NOT NULL,
    
    -- Engagement (denormalized)
    vote_score INT DEFAULT 0,
    upvote_count INT DEFAULT 0,
    downvote_count INT DEFAULT 0,
    reply_count INT DEFAULT 0,
    
    -- Hierarchy
    depth INT DEFAULT 0,
    path VARCHAR(500) NULL,
    
    -- Status
    is_deleted BOOLEAN DEFAULT FALSE,
    
    -- Timestamps
    edited_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    
    INDEX idx_post_id (post_id),
    INDEX idx_user_id (user_id),
    INDEX idx_parent_id (parent_id),
    INDEX idx_vote_score (vote_score),
    INDEX idx_post_score (post_id, vote_score),
    INDEX idx_created_at (created_at),
    FULLTEXT idx_content (content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Business Rules**:
- Content: 1-10000 characters
- Depth: Maximum 10 levels
- Path: Materialized path for efficient tree queries
- Soft delete: Show [deleted] placeholder

### post_votes
User votes on posts.

```sql
CREATE TABLE post_votes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    vote_type TINYINT NOT NULL COMMENT '1=upvote, -1=downvote',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_post_user (post_id, user_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Business Rules**:
- One vote per user per post
- Vote can be changed (upvote ↔ downvote)
- Vote can be removed (neutral)

### comment_votes
User votes on comments.

```sql
CREATE TABLE comment_votes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    comment_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    vote_type TINYINT NOT NULL COMMENT '1=upvote, -1=downvote',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_comment_user (comment_id, user_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Business Rules**:
- Same rules as post_votes

### bookmarks
User bookmarks for posts.

```sql
CREATE TABLE bookmarks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    post_id BIGINT UNSIGNED NOT NULL,
    
    -- Organization
    collection_name VARCHAR(100) NULL,
    notes TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_post (user_id, post_id),
    INDEX idx_user_collection (user_id, collection_name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Business Rules**:
- One bookmark per user per post
- Collections: Optional grouping

## Relationship Tables

### follows
User following relationships.

```sql
CREATE TABLE follows (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    follower_id BIGINT UNSIGNED NOT NULL,
    following_id BIGINT UNSIGNED NOT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_follow (follower_id, following_id),
    INDEX idx_following_id (following_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Business Rules**:
- User cannot follow themselves
- One follow per pair

### topic_subscriptions
User subscriptions to topics.

```sql
CREATE TABLE topic_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    topic_id BIGINT UNSIGNED NOT NULL,
    
    -- Settings
    notification_enabled BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_subscription (user_id, topic_id),
    INDEX idx_topic_id (topic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Notification System

### notifications
User notifications.

```sql
CREATE TABLE notifications (
    id CHAR(36) PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(255) NOT NULL,
    notifiable_type VARCHAR(255) NOT NULL,
    notifiable_id BIGINT UNSIGNED NOT NULL,
    data JSON NOT NULL,
    
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_unread (user_id, read_at),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notification Types**:
- CommentReply
- PostVoted
- CommentVoted
- UserFollowed
- MentionedInComment
- MentionedInPost

## Moderation System

### reports
User-submitted reports.

```sql
CREATE TABLE reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id BIGINT UNSIGNED NOT NULL,
    reportable_type VARCHAR(255) NOT NULL,
    reportable_id BIGINT UNSIGNED NOT NULL,
    
    reason ENUM('spam', 'harassment', 'hateful', 'violence', 'nsfw', 'misinformation', 'other') NOT NULL,
    description TEXT NULL,
    
    -- Status
    status ENUM('pending', 'reviewing', 'resolved', 'dismissed') DEFAULT 'pending',
    resolved_by BIGINT UNSIGNED NULL,
    resolved_at TIMESTAMP NULL,
    resolution_note TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_reportable (reportable_type, reportable_id),
    INDEX idx_status (status),
    INDEX idx_reporter_id (reporter_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### moderation_logs
Audit trail for moderation actions.

```sql
CREATE TABLE moderation_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    moderator_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(255) NOT NULL,
    target_id BIGINT UNSIGNED NOT NULL,
    reason TEXT NULL,
    metadata JSON NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (moderator_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_moderator_id (moderator_id),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## System Tables

### failed_jobs
Laravel failed queue jobs.

```sql
CREATE TABLE failed_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(255) UNIQUE NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_failed_at (failed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### sessions
User session storage.

```sql
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### cache
Cache storage.

```sql
CREATE TABLE cache (
    key VARCHAR(255) PRIMARY KEY,
    value MEDIUMTEXT NOT NULL,
    expiration INT NOT NULL,
    
    INDEX idx_expiration (expiration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Indexing Strategy

### Primary Indexes
- All primary keys (id columns)
- Unique constraints (email, username, slugs)

### Foreign Key Indexes
- All foreign key columns automatically indexed

### Query Optimization Indexes
- Composite indexes for common query patterns
- Sorting fields (score, created_at, hot_score)
- Filtering fields (status, type)

### Full-Text Indexes
- Post title and content
- Comment content
- Topic name and description

## Denormalization Strategy

### Counter Fields
Untuk performance, beberapa counter disimpan di parent table:
- `users.karma_score`
- `users.post_count`
- `users.comment_count`
- `topics.subscriber_count`
- `topics.post_count`
- `posts.vote_score`
- `posts.comment_count`
- `comments.reply_count`

### Score Fields
Pre-calculated scores untuk ranking:
- `posts.hot_score`
- `posts.controversy_score`

### Update Strategy
- Real-time update via events
- Periodic recalculation via scheduled jobs
- Cache invalidation on updates

## Data Integrity

### Foreign Key Constraints
- CASCADE on delete untuk dependent data
- SET NULL untuk optional relationships

### Check Constraints
```sql
ALTER TABLE post_votes
ADD CONSTRAINT check_vote_type CHECK (vote_type IN (1, -1));

ALTER TABLE comments
ADD CONSTRAINT check_depth CHECK (depth <= 10);
```

### Application-Level Validation
- Laravel Form Requests
- Model observers
- Business rule validation in services

## Performance Optimization

### Query Optimization
- Eager loading relationships
- Select only needed columns
- Use indexes effectively
- Avoid N+1 queries

### Caching Strategy
- Query result caching
- Model caching
- Fragment caching
- Full page caching (for guests)

### Partitioning (Future)
- Partition large tables by date
- Archive old data

## Backup Strategy

### Backup Schedule
- Full backup: Daily
- Incremental backup: Hourly
- Transaction log backup: Every 15 minutes

### Retention Policy
- Daily backups: 30 days
- Weekly backups: 90 days
- Monthly backups: 1 year

---
**Last Updated**: 2026-08-05  
**Document Owner**: Database Architect  
**Review Cycle**: Every schema change
