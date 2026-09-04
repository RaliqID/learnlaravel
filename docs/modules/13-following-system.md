# Module 13: Following System

## Status
✅ **COMPLETED (2026-08-08)**

## Overview
Sistem social following: follow/unfollow user, followers/following listing, follow status (`is_following`), dan notification ketika di-follow. Terintegrasi dengan Module 10 (Notification) dan Module 12 (User Profile). Semua infrastructure lokal — tidak ada external dependency.

## Database
### `follows` table (sesuai `database-design.md`)
```sql
CREATE TABLE follows (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    follower_id BIGINT UNSIGNED NOT NULL,
    following_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_follow (follower_id, following_id),
    INDEX idx_following_id (following_id),
    INDEX idx_created_at (created_at)
);
```
- Self-follow dicegah di application layer (422) + unique constraint mencegah duplicate di DB level.
- Tidak ada denormalized counter (`followers_count`) — listing via query langsung (sesuai instruksi: jangan tambah counter kecuali dibutuhkan).

## Architecture
```
FollowController → FollowService → FollowRepository → Follow model / DB
                     ↓ (follow baru)
              NotificationService::notifyUserFollowed → notifications table
```
- **Model**: `Follow` (UPDATED_AT = null, fillable follower_id/following_id), relasi `User::followers()` & `User::following()` (BelongsToMany, tanpa timestamps pivot karena tabel hanya created_at).
- **Repository**: `FollowRepositoryInterface` + `FollowRepository` — `isFollowing`, `follow` (idempotent via `insertOrIgnore`, race-proof), `unfollow` (idempotent), `followers`/`following` (paginated, join `follows` untuk order by `created_at` DESC, `withExists is_following` untuk viewer).
- **Service**: `FollowService` — guard self-follow (422), follow/unfollow idempotent, lists.
- **Controller**: `FollowController` (store, destroy, followers, following).
- **Resource**: `PublicUserResource` + field `is_following` (default false untuk guest).

## API Endpoints
```http
POST   /api/v1/users/{username}/follow           # Follow (auth)
DELETE /api/v1/users/{username}/follow           # Unfollow (auth)
GET    /api/v1/users/{username}/followers        # Followers list (public, paginated)
GET    /api/v1/users/{username}/following        # Following list (public, paginated)
```
- Route binding `{user:username}`; user soft-deleted / tidak ada → 404.
- Follow/unfollow: `auth:sanctum` + `throttle:60,1`. Listing: `throttle:feed`.

## Behavior
- **Follow**: idempotent. Follow baru → `created: true`, message "You are now following this user". Duplicate → `created: false`, "You are already following this user", tetap 200.
- **Unfollow**: idempotent. Hubungan ada → `removed: true`. Tidak ada → `removed: false`, "You were not following this user", tetap 200.
- **Self-follow**: 422 "You cannot follow yourself".
- **Guest**: follow/unfollow → 401. Listing followers/following tetap publik (200).
- **Banned user**: tidak bisa follow (middleware EnsureUserIsNotBanned → 403, konsisten moderation existing). Target banned tetap bisa di-follow (profile publik masih ada).

## Follow Status (`is_following`)
- Public profile (`GET /users/{username}`) → `is_following` true jika viewer authenticated mengikuti target; false jika guest.
- Implementasi: `loadExists(['followers as is_following' => where follower_id = viewerId])` — satu query (EXISTS), bukan N+1.
- Listing followers/following → `is_following` = apakah viewer mengikuti item tersebut (via `withExists`).

## Notification Integration
- Type baru: `Notification::TYPE_USER_FOLLOWED = 'UserFollowed'` (tabel notifications bebas enum — type string).
- `NotificationService::notifyUserFollowed(User $followed, int $followerId)` — skip self-follow.
- Dipanggil **hanya saat follow relationship BARU dibuat** (`$created === true`), bukan duplicate → duplicate follow tidak menghasilkan notification berulang (test khusus).
- In-sync dengan operasi follow (satu request); bukan async — mengikuti arsitektur notification existing.

## Security
- Follow/unfollow hanya authenticated (401 untuk guest).
- User tidak bisa memanipulasi relationship user lain (endpoint follow hanya untuk current user sebagai follower).
- Self-follow ditolak (422).
- Unique constraint mencegah duplicate record.
- Listing & public profile tidak pernah expose: email, password, token, role, is_banned, internal data.

## Performance
- `follows` index: unique (follower_id, following_id), index following_id, index created_at.
- Listing followers/following: join `follows` + order by `f.created_at` (indexed) + pagination; `withExists` = subquery EXISTS (bukan N+1).
- Follow/unfollow: `insertOrIgnore` / `delete` by unique pair — constant-time.

## Tests
- `tests/Feature/FollowingSystemTest.php` — **19 tests, 89 assertions, semua PASS**.
- Coverage: follow, unfollow, guest 401, self-follow 422, duplicate follow (record & notification), unfollow idempotent, followers list, following list, pagination, public data only, private fields tidak bocor, is_following (authenticated & guest), is_following di listing, follow notification, duplicate tidak bikin notification, 404 target tidak ada / soft-deleted, banned user tidak bisa follow.

## Files
### Created
- `database/migrations/2026_08_08_083411_create_follows_table.php`
- `app/Models/Follow.php`
- `app/Repositories/FollowRepositoryInterface.php`
- `app/Repositories/FollowRepository.php`
- `app/Services/FollowService.php`
- `app/Http/Controllers/Api/V1/FollowController.php`
- `tests/Feature/FollowingSystemTest.php`
- `docs/modules/13-following-system.md`

### Modified
- `app/Models/User.php` — relasi `followers()` & `following()`
- `app/Models/Notification.php` — `TYPE_USER_FOLLOWED`
- `app/Services/NotificationService.php` — `notifyUserFollowed()`
- `app/Http/Resources/Api/V1/PublicUserResource.php` — field `is_following`
- `app/Services/UserProfileService.php` — `publicProfile($user, $viewerId)` + `loadExists is_following`
- `app/Http/Controllers/Api/V1/UserProfileController.php` — `show` pass viewer id
- `app/Providers/AppServiceProvider.php` — binding `FollowRepositoryInterface`
- `routes/api.php` — routes follow/followers/following
- `docs/project-status.md`, `docs/module-index.md`, `docs/SESSION_HANDOVER.md`

## Database Changes
- Tabel `follows` (unique pair, FK cascade ke users, index following_id + created_at). Tanpa updated_at (design hanya created_at).

## External Dependencies
NONE — semua infrastructure lokal. Tidak ada Human Action Required.

## Bug Found & Fixed (sesi ini)
- **Parameter name mismatch route binding**: controller method `store(User $target)` tidak cocok dengan route parameter `{user:username}` (bernama `user`) → Laravel meng-inject User kosong (id null) → `follow(int, null)` TypeError 500.
  - Fix: parameter controller diganti `$target` → `$user` agar match route param `user`.
- **Test followers list order non-deterministik**: `created_at` same-second precision → urutan `orderByDesc('created_at')` tidak pasti.
  - Fix: test tidak lagi mengandalkan urutan spesifik (sort usernames lalu assert).

## Test Result (aktual)
```
php artisan test tests/Feature/FollowingSystemTest.php
→ 19 passed (89 assertions)

php artisan test
→ 275 passed (1279 assertions), 0 failed
```
