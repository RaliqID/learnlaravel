# Module 11: Search System

## Status
✅ **COMPLETED (2026-08-08)**

## Overview
Search konten LaraNews menggunakan **MySQL FULLTEXT** (Infrastructure lokal, sesuai `database-design.md`: `FULLTEXT idx_title_content (title, content)` dan `decision-log.md`: "Built-in full-text search"). Tidak membutuhkan external search engine (Meilisearch/Elasticsearch/Algolia) — tidak ada Human Action Required.

## Database
### Migration baru
1. `add_fulltext_index_to_posts_table` — menambah `FULLTEXT idx_title_content (title, content)` pada `posts` (sesuai database-design).
2. `rebuild_posts_fulltext_index` — `OPTIMIZE TABLE posts`. InnoDB FULLTEXT index yang dibuat pada tabel kosong tidak "activated": baris baru tidak dapat di-match `MATCH() AGAINST()` sampai index dibangun ulang dengan minimal satu dokumen (kondisi yang otomatis terjadi di production saat data sudah ada). Migration ini memastikan index terbangun penuh.

## Architecture
```
SearchController → SearchService → PostRepository::searchPosts (FULLTEXT)
                        ├→ TopicRepository::searchActive (LIKE, active only)
                        └→ User model (LIKE, non-banned)
```
- **Controller**: `SearchController` (index, posts, topics, users).
- **Service**: `SearchService` (searchPosts via PostRepositoryInterface, searchTopics via TopicRepositoryInterface, searchUsers via User model).
- **Request**: `SearchRequest` (q required min1 max100; sort relevance|newest|oldest|popular; topic_id exists; time; page; per_page max100).
- **Resource**: `PostResource` (posts), `TopicResource` (topics), `UserSearchResource` baru (profil publik minimal — **tanpa email/bio/website/location** untuk mencegah kebocoran data privat).
- **Routes**: `/api/v1/search*` dengan `throttle:feed` (30/min guest, 60/min auth) — konsisten endpoint publik read.

## API Endpoints
```http
GET    /api/v1/search            # Global post search
GET    /api/v1/search/posts      # Post search (alias)
GET    /api/v1/search/topics     # Topic search (active only)
GET    /api/v1/search/users      # User search (non-banned)
```

### Parameter post search
| Param | Values | Default |
|-------|--------|---------|
| `q` | required, string, 1–100 | — |
| `sort` | relevance, newest, oldest, popular | relevance |
| `topic_id` | integer (exists:topics) | — |
| `time` | hour, day, week, month, year, all | all |
| `page` | integer ≥1 | 1 |
| `per_page` | integer 1–100 | 20 |

### Sorting
- `relevance` (default): `MATCH(title, content) AGAINST(? IN BOOLEAN MODE) DESC`
- `newest` / `oldest`: `published_at` DESC / ASC
- `popular`: `hot_score` DESC

### Response
```json
{
  "status": "success",
  "data": [PostResource ...],
  "meta": {
    "query": "...",
    "sort": "relevance",
    "pagination": { "total": 0, "count": 0, "per_page": 20, "current_page": 1, "last_page": 1 },
    "timestamp": "..."
  }
}
```

## Security & Privacy
- **Post search hanya menampilkan konten publik**: base query `published + approved + visible` (bukan draft/archived), soft-delete otomatis dikecualikan. Draft/unapproved/archived/locked/deleted **tidak pernah bocor** (test khusus).
- **SQL injection aman**: FULLTEXT & LIKE semuanya parameter binding.
- **User search**: hanya user non-banned; resource publik minimal (tanpa email/bio/location).
- **Topic search**: hanya topic aktif.

## Performance
- FULLTEXT index (title, content) untuk keyword search — bukan full table scan untuk search.
- `LIKE` hanya dipakai topics (volume kecil) & users (volume kecil saat ini).
- Eager loading `user` + `topic` (LIST_WITH) menghindari N+1.
- Pagination (default 20, max 100) + index `published_at`, `hot_score` untuk sorting.
- Rate limiter `feed` membatasi abuse.

## Tests
- `tests/Feature/SearchSystemTest.php` — **20 tests, 72 assertions, semua PASS**.
- Coverage: search published, by title, by content, relevance, pagination, sort newest/oldest/popular, topic filter, time filter, draft tidak bocor, archived tidak bocor, unapproved tidak bocor, locked tidak bocor, deleted tidak bocor, empty state, invalid query 422, endpoint alias, topics active-only, users non-banned.

## Test Environment Change (penting)
- `phpunit.xml` diubah: **DB_CONNECTION = mysql**, DB `learnlaravel_test` (sebelumnya sqlite `:memory:`).
- Alasan: FULLTEXT index & `MATCH() AGAINST()` adalah fitur MySQL (database-design source of truth); SQLite tidak mendukung FULLTEXT. Menjalankan test di driver yang sama dengan production menghilangkan ketidaksesuaian driver (case-sensitivity, JSON, decimal, dll).
- Database test `learnlaravel_test` dibuat (utf8mb4_unicode_ci).
- Quirk InnoDB FULLTEXT: index yang dibuat saat tabel kosong tidak match sampai di-rebuild; helper `searchRequest()` di SearchSystemTest menjalankan `OPTIMIZE TABLE posts` sebelum request (meniru kondisi production yang index-nya sudah terbangun).

## Files
### Created
- `database/migrations/2026_08_08_080701_add_fulltext_index_to_posts_table.php`
- `database/migrations/2026_08_08_081355_rebuild_posts_fulltext_index.php`
- `app/Services/SearchService.php`
- `app/Http/Controllers/Api/V1/SearchController.php`
- `app/Http/Requests/Api/V1/SearchRequest.php`
- `app/Http/Resources/Api/V1/UserSearchResource.php`
- `tests/Feature/SearchSystemTest.php`
- `docs/modules/11-search-system.md`

### Modified
- `app/Repositories/PostRepository.php` + Interface — `searchPosts()`, `applySearchSort()`
- `app/Repositories/TopicRepository.php` + Interface — `searchActive()`
- `routes/api.php` — route `/api/v1/search*`
- `phpunit.xml` — test DB MySQL (`learnlaravel_test`)
- `docs/project-status.md`, `docs/module-index.md`, `docs/SESSION_HANDOVER.md`

## Future / Not Implemented
- Search users lanjutan (filter post count, following), search di comments, search suggestions/autocomplete — di luar scope Module 11.
- Meilisearch/Elasticsearch (project-overview aspirasi) — tidak diperlukan untuk arsitektur saat ini.

## Test Result (aktual)
```
php artisan test tests/Feature/SearchSystemTest.php
→ 20 passed (72 assertions)

php artisan test
→ 234 passed (1119 assertions), 0 failed
```
