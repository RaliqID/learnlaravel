# Module 17: Analytics

## Status
✅ **COMPLETED (2026-08-08)**

## Overview
Analytics API untuk Admin — metrics platform, user activity, content performance, dan moderation metrics. Semua dihitung **live** dari tabel existing via aggregation queries (COUNT / SUM(CASE) / GROUP BY). **Tidak ada tabel/event tracking baru** — metric hanya yang dapat dihitung valid dari schema sekarang.

## Data Sources (audit)
| Metric | Sumber | Kolom |
|--------|--------|-------|
| Users / registration trend | `users` | `created_at`, `karma_score` |
| Posts | `posts` | `status`, `published_at`, `created_at`, `deleted_at` |
| Views / votes / bookmarks / comments per post | `posts` | `view_count`, `vote_score`, `bookmark_count`, `comment_count`, `hot_score` |
| Comments | `comments` | `created_at`, `is_deleted`, `deleted_at` |
| Voting activity | `post_votes`, `comment_votes` | `created_at` |
| Bookmarking activity | `bookmarks` | `created_at` |
| Following activity | `follows` | `created_at` |
| Reports | `reports` | `status`, `reason`, `created_at` |
| Moderation actions | `moderation_logs` | `action`, `target_type`, `created_at` |
| Top topics | `topics` | `post_count`, `subscriber_count`, `is_active` |

## Architecture Decision
- Analytics = **aggregation queries langsung** pada existing tables — tidak ada `analytics_events`/`analytics` tracking table (tidak ada business requirement untuk historical event data yang tidak direpresentasikan).
- **Historical/time-series**: hanya dapat direkonstruksi dari `created_at`/`published_at` timestamps — valid untuk users/posts/comments/votes/bookmarks/follows/reports/moderation_logs. Tidak ada metrik "active users" berbasis session (tidak ada data session di schema) → **dikecualikan**.
- **Cache**: tidak ditambahkan Redis; query aggregation efisien. Caching bisa ditambahkan di Module 19 (Performance) jika dibutuhkan.
- Hot-score logic **tidak diduplikasi** — analytics pakai field denormalized existing (`hot_score`, `vote_score`, `view_count`).

## API Endpoints (admin only)
```http
GET /api/v1/admin/analytics/overview    # Platform totals (per period)
GET /api/v1/admin/analytics/users       # Activity series (registration/posts/comments/votes per day/month)
GET /api/v1/admin/analytics/content     # Top viewed/voted/bookmarked/commented + top topics
GET /api/v1/admin/analytics/moderation  # Reports + moderation action metrics
```
Semua `auth:sanctum` + `can:admin-area` + `throttle:60,1`.

### Parameters (AnalyticsRequest)
| Param | Values | Default |
|-------|--------|---------|
| `period` | today, 7d, 30d, 90d, all | 30d |
| `granularity` | day, month (users endpoint) | day |
| `limit` | integer 1–50 (content endpoint) | 10 |

## Metrics Implemented
- **Overview**: total users, posts, comments, post_votes, comment_votes, bookmarks, follows, reports, moderation_actions.
- **User activity**: series per bucket (day/month) untuk users, posts, comments, post_votes — di-filter period.
- **Content performance**: total_views, most_viewed, most_voted, most_bookmarked, most_commented, top_topics — **hanya published & bukan soft-deleted** (draft/deleted tidak masuk analytics).
- **Moderation**: reports total/open/resolved/dismissed + by_type; moderation_actions total + by_action + over_time.

## Authorization
- Guest → 401
- Regular user → 403
- Moderator → 403 (analytics = admin-level)
- Admin → 200
- Tidak ada role baru.

## Performance
- Satu query per metric group (COUNT/SELECT SUM CASE/GROUP BY DATE) — tidak ada N+1, tidak ada foreach+query.
- Index existing: `created_at` pada users/posts/comments/reports/moderation_logs; `post_votes`/`bookmarks`/`follows` punya `created_at` index.
- `contentPerformance` membatasi hasil (limit, max 50) & memfilter published non-deleted.

## Files Created
- `app/Services/AnalyticsService.php`
- `app/Http/Controllers/Api/V1/Admin/AnalyticsController.php`
- `app/Http/Requests/Api/V1/Admin/AnalyticsRequest.php`
- `tests/Feature/AnalyticsSystemTest.php` (15 tests, 44 assertions)
- `docs/modules/17-analytics.md`

## Files Modified
- `routes/api.php` — 4 analytics routes
- `docs/project-status.md`, `docs/module-index.md`, `docs/SESSION_HANDOVER.md`

## Database Changes
NONE — tidak ada migration/index baru (index created_at sudah ada).

## Bugs Found & Fixed
- **Test determinisme**: `Post::factory()` & `Comment::factory()` otomatis membuat `User`/`Topic` → hitungan user di test membengkak. Fix: set `user_id` eksplisit; assertion count absolut diganti dengan perbandingan terhadap `Model::count()` di dalam test (robust terhadap environment).

## Test Result (aktual)
```
php artisan test tests/Feature/AnalyticsSystemTest.php
→ 15 passed (44 assertions)

php artisan test
→ 371 passed (1553 assertions), 0 failed
```

## Known Limitations
- Tidak ada metrik "active users" berbasis session (tidak ada data session/log-activity di schema).
- Time-series hanya seakurat timestamps `created_at`/`published_at` — bukan peristiwa event harian yang detail.
- Total views = jumlah dari `posts.view_count` (aggregasi field denormalized, bukan event log).
- Caching belum ditambahkan — untuk skala besar bisa di Module 19.
