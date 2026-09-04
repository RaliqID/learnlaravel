# Module 12: User Profile

## Status
✅ **COMPLETED (2026-08-08)**

## Overview
Sistem User Profile yang terintegrasi dengan Authentication, Posts, Comments, dan Karma. Tidak ada external dependency (avatar hanya URL string, bukan upload — schema `users.avatar` sudah mendukung).

## API Endpoints
```http
GET  /api/v1/users/{username}            # Public profile
GET  /api/v1/users/{username}/posts      # Public posts (published only, paginated)
GET  /api/v1/users/{username}/comments   # Public comments (non-deleted, paginated)
PUT  /api/v1/users/{username}            # Update profile (owner/admin)
GET  /api/v1/user                        # Own profile (auth) — di-upgrade ke format resource konsisten
```
- Public read: `throttle:feed` (30/min guest, 60/min auth).
- Update: `auth:sanctum` + `throttle:60,1`.
- Route binding `{user:username}` resolve by username; user soft-deleted → 404.

## Architecture
```
UserProfileController → UserProfileService → PostRepository / CommentRepository / User model
```
- **Controller**: `UserProfileController` (show, update, posts, comments).
- **Service**: `UserProfileService` (publicProfile via loadCount, updateProfile, userPosts, userComments).
- **Repository**: `PostRepository::getUserPublicPosts` (publicBaseQuery: published+approved+visible), `CommentRepository::getUserComments` (non-deleted, whereHas post public).
- **Resource**: `PublicUserResource` (baru, public aman), `UserResource` (own profile / update response), `PostResource`, `CommentResource`.
- **Request**: `UpdateProfileRequest`, `ProfileListRequest`.

## Public Profile Fields (PublicUserResource)
`id`, `username`, `display_name`, `avatar`, `bio`, `website`, `location`, `karma_score`, `post_count`, `comment_count`, `is_verified`, `created_at`, dan `email` **hanya jika** `show_email = true` (opt-in).

**Tidak pernah di-expose**: email (default), `role`, `is_banned`, `banned_until`, `banned_reason`, `show_email`/`email_notifications` (settings), `password`, token.

## Profile Update (UpdateProfileRequest)
| Field | Rules |
|-------|-------|
| `username` | sometimes, string, 3–50, `regex:/^[a-zA-Z0-9_-]+$/`, unique (ignore self) |
| `display_name` | sometimes, string, 1–100 |
| `bio` | sometimes, nullable, string, max 500 |
| `avatar` | sometimes, nullable, url, max 255 |
| `website` | sometimes, nullable, url, max 255 |
| `location` | sometimes, nullable, string, max 100 |
| `show_email` | sometimes, boolean |
| `email_notifications` | sometimes, boolean |

Authorization: `UserPolicy::update` (owner **atau** admin) — User A tidak bisa mengubah profile User B (403).

## Statistics
`post_count` & `comment_count` dihitung **real** via `withCount` pada relasi `User::posts()` (published+approved+visible) dan `User::comments()` (non-deleted). Kolom denormalized `users.post_count`/`comment_count` **tidak di-sync** oleh PostService/CommentService sehingga TIDAK dipakai (tidak akurat). Satu query dengan relasi — bukan query berulang per statistik. `karma_score` dari kolom (di-sync VoteService).

## Security
- Public profile aman: tidak ada email (kecuali opt-in), tidak ada field internal/moderation.
- User posts: hanya published+approved+visible; draft/archived/unapproved/locked/deleted tidak muncul.
- User comments: hanya non-deleted + post-nya public (published+approved+visible); comment di post draft tidak bocor.
- Soft-deleted user → 404 (route binding default).
- Mass assignment: hanya field fillable yang tervalidasi (username, display_name, bio, avatar, website, location, show_email, email_notifications).

## Tests
- `tests/Feature/UserProfileTest.php` — **22 tests, 71 assertions, semua PASS**.
- Coverage: public profile, expected fields, private tidak bocor, email opt-in, own profile, update own, update other → 403, guest update → 401, username validation, duplicate username, display name, bio, update username (route tetap), posts pagination, only public posts, deleted posts, comments pagination, deleted/hidden comments tidak bocor, comments di post unpublished tidak bocor, statistics benar, 404 user tidak ada, 404 user soft-deleted.

## Files
### Created
- `app/Http/Resources/Api/V1/PublicUserResource.php`
- `app/Services/UserProfileService.php`
- `app/Http/Controllers/Api/V1/UserProfileController.php`
- `app/Http/Requests/Api/V1/UpdateProfileRequest.php`
- `app/Http/Requests/Api/V1/ProfileListRequest.php`
- `tests/Feature/UserProfileTest.php`
- `docs/modules/12-user-profile.md`

### Modified
- `app/Models/User.php` — relasi `posts()` & `comments()`
- `app/Repositories/PostRepository.php` + Interface — `getUserPublicPosts()`
- `app/Repositories/CommentRepository.php` + Interface — `getUserComments()`
- `routes/api.php` — routes `/api/v1/users/*` + upgrade `/user` (own profile resource)
- `docs/project-status.md`, `docs/module-index.md`, `docs/SESSION_HANDOVER.md`

## Database Changes
- Tidak ada migration baru (semua kolom sudah ada di schema users).
- Perilaku: statistik profile memakai `withCount` (real), bukan kolom denormalized yang stale.

## External Dependencies
NONE — avatar sebagai URL string (bukan upload file); tidak butuh S3/Cloudinary/etc. Tidak ada Human Action Required.

## Future / Not Implemented
- Avatar file upload (butuh storage disk & upload infra) — di luar scope; avatar via URL sudah didukung.
- Following/followers (Module 13) — belum ada kolom/relasi.
- Banned user: profile tetap tampil (identitas publik), postingan hanya published.

## Test Result (aktual)
```
php artisan test tests/Feature/UserProfileTest.php
→ 22 passed (71 assertions)

php artisan test
→ 256 passed (1190 assertions), 0 failed
```
