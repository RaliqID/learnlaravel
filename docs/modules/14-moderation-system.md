# Module 14: Moderation System

## Status
✅ **COMPLETED (2026-08-08)**

## Overview
Sistem moderasi: content reporting (post & comment), moderation queue, report resolution, content removal/restore, user ban/unban, audit trail (`moderation_logs`), dan moderation notifications. Security-critical — authorization tegas (user biasa → 403, guest → 401, admin/moderator → 200). Semua infrastructure lokal, tidak ada external dependency.

## Database (sesuai `database-design.md`)
### `reports`
- `reporter_id` FK users (cascade), `reportable_type` + `reportable_id` (polymorphic: Post/Comment)
- `reason` ENUM(spam, harassment, hateful, violence, nsfw, misinformation, other)
- `description` TEXT nullable
- `status` ENUM(pending, reviewing, resolved, dismissed) default pending
- `resolved_by` FK users (SET NULL), `resolved_at`, `resolution_note`
- Index: (reportable_type, reportable_id), status, reporter_id, created_at

### `moderation_logs` (audit trail)
- `moderator_id` FK users (cascade), `action` VARCHAR, `target_type` + `target_id` (polymorphic)
- `reason` TEXT, `metadata` JSON, `created_at`
- Index: moderator_id, (target_type, target_id), created_at
- Tidak menyimpan secret/token/password.

## API Endpoints
```http
POST   /api/v1/reports                               # Submit report (auth)
GET    /api/v1/moderation/reports                    # Queue (mod/admin) — filter status/type, pagination
GET    /api/v1/moderation/reports/{report}           # Detail (mod/admin)
PUT    /api/v1/moderation/reports/{report}           # Resolve/dismiss (mod/admin)
POST   /api/v1/moderation/posts/{post}/remove        # Remove post (mod/admin)
POST   /api/v1/moderation/posts/{post}/restore       # Restore post (mod/admin, route withTrashed)
POST   /api/v1/moderation/comments/{comment}/remove  # Remove comment (mod/admin)
POST   /api/v1/moderation/comments/{comment}/restore # Restore comment (mod/admin, route withTrashed)
POST   /api/v1/moderation/users/{username}/ban       # Ban user (mod/admin, UserPolicy::ban)
POST   /api/v1/moderation/users/{username}/unban     # Unban user (mod/admin, UserPolicy::unban)
```
Semua `auth:sanctum` + `throttle:60,1`.

## Architecture
```
ReportController / ModerationController → ModerationService → ReportRepository / PostService / CommentService / ModerationLog
                                            ↓
                                   NotificationService (moderation notifications)
```
- **Service**: `ModerationService` — submit, queue, detail, resolve, remove/restore post & comment, ban/unban, audit log.
- **Repository**: `ReportRepositoryInterface` + `ReportRepository` (create, find, duplicate-check, listForModeration, update).
- **Reuse existing lifecycle**: post removal/restore via `PostService::destroy/restore` (soft delete + topic count + hot score + cache invalidation); comment via `CommentService::deleteComment` + `restoreComment` (baru).
- **Policies**: `ReportPolicy` (create=authenticated; viewAny/view/update=mod/admin), `ModerationPolicy` (Gate `moderate` untuk remove/restore content).
- **Ban/unban**: reuse `UserPolicy::ban`/`unban` (admin/moderator; tidak bisa ban admin/self).

## Behavior & Rules
- **Report**: authenticated user, valid target (trashed/deleted → 404), reason validated. Duplicate **open** report oleh user yang sama → **409 Conflict** (anti-spam). Reporter tidak bisa resolve (403).
- **Queue**: hanya admin/moderator. Filter `status` (pending/reviewing/resolved/dismissed) & `type` (post/comment, di-map ke class FQCN), sort newest/oldest, pagination (default 20, max 100).
- **Resolve**: hanya report yang masih open (pending/reviewing); resolved/dismissed → 422. Wajib set `status` resolved|dismissed + `resolution_note` opsional.
- **Remove post**: soft delete (deleted_at). Tidak muncul di feed/search/profile/related (publicBaseQuery exclude trashed; cache di-invalidate). Remove berulang → 422.
- **Restore post**: hanya jika trashed; kembali ke lifecycle semula (published tetap published; draft tidak otomatis dipublish). Feed cache di-rebuild.
- **Remove/restore comment**: soft delete; hidden dari post comments.
- **Ban**: `is_banned=true`, `banned_until` (null=permanent / `duration_days`), `banned_reason`. Banned user: middleware blokir aksi (403), login ditolak (403), data existing dipertahankan.
- **Unban**: reset `is_banned=false`, `banned_until=null`, `banned_reason=null`.

## Authorization
- Guest → 401 (semua endpoint moderasi/report).
- User biasa → 403 (queue, detail, resolve, remove, restore, ban, unban).
- Moderator/Admin → 200.
- Tidak bisa ban diri sendiri / sesama admin (UserPolicy::ban).
- Moderator tidak bisa melakukan action di luar permission (remove/restore/ban/unban semuanya via `moderate` Gate + UserPolicy).

## Notification Integration (type baru)
- `ContentRemoved` → owner post/comment saat konten dihapus (skip jika moderator = owner).
- `UserBanned` / `UserUnbanned` → user yang di-ban/unban (skip self-ban, dicegah policy).
- `ReportResolved` → reporter saat report di-resolve (skip jika reporter = moderator).
- Semua dibuat hanya setelah action sukses (di dalam transaction yang sama).

## Cache Invalidation
- Remove/restore post → `PostService` memanggil `invalidateCaches`: `FeedService::invalidate()` (registry), `TopicService::invalidate()`, `post:related`, `post:navigation`.
- Remove/restore comment → `CommentService` memanggil `invalidateCache` (feed + post:related + post:navigation).
- Test membuktikan: removed post hilang dari feed, search, dan profile; restore mengembalikannya ke feed.

## Security
- Moderator fields tidak expose ke public (ban reason hanya di endpoint moderasi; PublicUserResource tidak menampilkan is_banned/banned_until).
- ReportResource hanya untuk mod/admin (guard di policy).
- Audit trail menyimpan actor/action/target/reason/metadata/timestamp — tanpa secret.
- Mass assignment aman: hanya field tervalidasi via FormRequest.

## Tests
- `tests/Feature/ModerationSystemTest.php` — **42 tests, 110 assertions, semua PASS**.
- Coverage: report post/comment, guest 401, invalid report 422, duplicate 409, report target terhapus 404, user tidak bisa manipulasi status, queue (moderator/admin/regular 403/guest 401), filter status/type, detail, resolve + 422 already-resolved, remove post/comment, regular user 403, removed hidden dari feed/search/profile/post-comments, ContentRemoved notification, restore post/comment, regular user 403 restore, restore → feed kembali, restore non-removed 422, ban/unban, regular user 403, self-ban 403, admin-ban-admin 403, banned user aksi dibatasi, banned user login 403, ban/unban notification, guest 401 semua endpoint, moderation logs tercatat.

## Files
### Created
- `database/migrations/2026_08_08_085007_create_reports_and_moderation_logs_tables.php`
- `app/Models/Report.php`, `app/Models/ModerationLog.php`
- `database/factories/ReportFactory.php`
- `app/Repositories/ReportRepositoryInterface.php`, `app/Repositories/ReportRepository.php`
- `app/Services/ModerationService.php`
- `app/Http/Controllers/Api/V1/ReportController.php`, `app/Http/Controllers/Api/V1/ModerationController.php`
- `app/Http/Requests/Api/V1/StoreReportRequest.php`, `ReportListRequest.php`, `ResolveReportRequest.php`, `BanUserRequest.php`
- `app/Policies/ReportPolicy.php`, `app/Policies/ModerationPolicy.php`
- `app/Http/Resources/Api/V1/ReportResource.php`
- `tests/Feature/ModerationSystemTest.php`
- `docs/modules/14-moderation-system.md`

### Modified
- `app/Services/CommentService.php` — `restoreComment()` (moderator/admin, count & cache)
- `app/Services/NotificationService.php` — 4 method moderasi + import Report
- `app/Models/Notification.php` — 4 type baru (ContentRemoved, UserBanned, UserUnbanned, ReportResolved)
- `database/factories/CommentFactory.php` — default `post_id` (bug laten MySQL strict)
- `app/Providers/AppServiceProvider.php` — binding ReportRepositoryInterface + Gate `moderate`
- `routes/api.php` — report & moderation routes (dengan `->withTrashed()` untuk restore)
- `docs/project-status.md`, `docs/module-index.md`, `docs/SESSION_HANDOVER.md`

## Bugs Found & Fixed
1. **CommentFactory tanpa `post_id` default** (bug laten Module 8): `Comment::factory()` tanpa post_id → SQL error 1364 di MySQL strict (tidak terdeteksi di SQLite non-strict). Fix: tambah `post_id => Post::factory()` di factory definition.
2. **Route binding param mismatch di controller ban**: nama param harus match route param (sudah benar `$user`).
3. **Test instance stale setelah ban**: route binding instance ≠ test instance; `Sanctum::actingAs($target)` memakai object stale (`is_banned=false`). Fix test: `$target->refresh()` sebelum actingAs. (Production aman — token resolve fresh dari DB.)
4. **Feed response shape**: response feed membungkus di `data.posts`; test awal assert `data` salah → diperbaiki ke `data.posts`.
5. **`type` filter mismatch**: reportable_type disimpan FQCN (`App\Models\Post`); filter `type=post` perlu mapping → class.
6. **`CommentService::delete`** seharusnya `deleteComment` (method name).
7. **Test non-deterministik** (ReportFactory random comment/post) — dibuat eksplisit.

## External Dependencies
NONE — semua infrastructure lokal. Tidak ada Human Action Required.

## Test Result (aktual)
```
php artisan test tests/Feature/ModerationSystemTest.php
→ 42 passed (110 assertions)

php artisan test
→ 317 passed (1389 assertions), 0 failed
```
