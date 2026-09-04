# Module 10: Notification System

## Status
✅ **COMPLETED (2026-08-08)**

## Overview
Notification System in-app (database) untuk event yang memang sudah didukung system: comment reply, comment pada post, post vote, dan comment vote. Tidak ada external dependency — tidak menggunakan email/push/websocket (in-app notification murni, sesuai desain).

## Database
### `notifications` table
```sql
CREATE TABLE notifications (
    id CHAR(36) PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(255) NOT NULL,
    notifiable_type VARCHAR(255) NOT NULL,
    notifiable_id BIGINT UNSIGNED NOT NULL,
    data JSON NOT NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id, read_at),
    INDEX (created_at),
    INDEX (notifiable_type, notifiable_id)
);
```
- `id` UUID string (di-generate model saat creating, bukan auto-increment).
- `user_id` = penerima notification (recipient). `notifiable` = objek yang memicu (Post/Comment).
- Hanya `created_at` (bukan updated_at) — notification tidak di-update (kecuali `read_at`).

### Notification Types (const di `Notification` model)
- `CommentReply` — comment mendapat reply (recipient = pemilik parent comment)
- `CommentPosted` — post mendapat top-level comment (recipient = pemilik post)
- `PostVoted` — post di-vote (recipient = pemilik post)
- `CommentVoted` — comment di-vote (recipient = pemilik comment)

## Architecture
```
NotificationController → NotificationService → NotificationRepository → Notification model
                            ↑ (trigger dari CommentService & VoteService)
```
- **Model**: `App\Models\Notification` — uuid id, morphTo notifiable, belongsTo user, scope `unread()` & `forUser()`, `markAsRead()`, `is_read` accessor.
- **Repository**: `NotificationRepositoryInterface` + `NotificationRepository` (listForUser paginated, unreadCount, findForUser, create, markAsRead, markAllAsRead, delete).
- **Service**: `NotificationService` (listForUser, unreadCount, markAsRead, markAllAsRead, delete, send) + 4 factory method trigger (notifyCommentReply, notifyCommentPosted, notifyPostVoted, notifyCommentVoted) yang skip self-activity.
- **Controller**: `NotificationController` (index, show, read, readAll, destroy, unreadCount).
- **Resource**: `NotificationResource` (id, type, data, read_at, is_read, created_at, notifiable).
- **Policy**: `NotificationPolicy` — view/update/delete hanya jika `user_id === notification->user_id` (user isolation).
- **Validation**: `NotificationListRequest` (unread `in:0,1,true,false`, page, per_page max 100).

## API Endpoints
```http
GET    /api/v1/notifications                    # List (paginated, ?unread=true)
GET    /api/v1/notifications/unread-count       # Unread count
GET    /api/v1/notifications/{notification}     # Detail
PUT    /api/v1/notifications/{notification}/read  # Mark single as read
PUT    /api/v1/notifications/read-all           # Mark all as read
DELETE /api/v1/notifications/{notification}     # Delete
```
Semua `auth:sanctum` + `throttle:60,1`. User hanya bisa mengakses notification miliknya (policy → 403 untuk milik user lain).

## Triggers (integrasi existing modules)
- **CommentService::createComment** → `notifyCommentReply` (jika reply) + `notifyCommentPosted` (jika top-level). Skip jika actor = recipient.
- **CommentService::voteComment** → `notifyCommentVoted` saat vote berubah.
- **VoteService::applyEffect** (post vote/switch/remove) → `notifyPostVoted` saat scoreDelta != 0. Skip jika voter = post owner.
- Semua di dalam transaksi yang sama dengan operasi sumber (atomicity — notification rollback bila operasi gagal).
- Tidak ada cache khusus notification (query langsung, indexed; unread count memakai `INDEX(user_id, read_at)`).

## Security & Performance
- User isolation: policy memaksa `user_id === notification->user_id` pada view/update/delete.
- Mass assignment: hanya field `fillable` (id, user_id, type, notifiable, data, read_at).
- Pagination default 20, max 100 per page.
- Index (user_id, read_at) untuk list unread & unread count; index (notifiable_type, notifiable_id) untuk lookup notifiable.

## Tests
- `tests/Feature/NotificationSystemTest.php` — 13 tests, 54 assertions, semua PASS.
- Coverage: reply trigger, top-level trigger, post vote trigger, self-vote skip, list, unread filter, guest 401, unread count, mark read, mark all, read other user 403, delete own, delete other 403.

## Bugs Found & Fixed (saat implementasi)
1. **Duplicate class `StoreReplyRequest`** (bug laten Module 08): dideklarasikan di `StoreCommentRequest.php` (bersama StoreCommentRequest & UpdateCommentRequest) DAN di file terpisah `StoreReplyRequest.php`. Terpapar fatal error `Cannot declare class` ketika kedua request dipakai dalam satu proses.
   - **Fix**: `StoreCommentRequest.php` hanya berisi class `StoreCommentRequest`.
2. **`StoreReplyRequest::authorize()` salah**: hasil artisan `return false` (tidak pernah terbaca efektif karena tertutup duplikat) → endpoint reply selalu 403 setelah duplikat dihapus.
   - **Fix**: `authorize()` → `true` + rules content (konsisten `StoreCommentRequest`).

## Files
### Created
- `database/migrations/2026_08_08_064752_create_notifications_table.php`
- `app/Models/Notification.php`
- `database/factories/NotificationFactory.php`
- `app/Repositories/NotificationRepositoryInterface.php`
- `app/Repositories/NotificationRepository.php`
- `app/Services/NotificationService.php`
- `app/Http/Controllers/Api/V1/NotificationController.php`
- `app/Http/Resources/Api/V1/NotificationResource.php`
- `app/Http/Requests/Api/V1/NotificationListRequest.php`
- `app/Policies/NotificationPolicy.php`
- `tests/Feature/NotificationSystemTest.php`
- `docs/modules/10-notification-system.md`

### Modified
- `app/Services/CommentService.php` (inject NotificationService + 2 trigger)
- `app/Services/VoteService.php` (inject NotificationService + trigger)
- `app/Http/Requests/Api/V1/StoreCommentRequest.php` (hapus 2 class duplikat)
- `app/Http/Requests/Api/V1/StoreReplyRequest.php` (authorize true + rules)
- `app/Providers/AppServiceProvider.php` (binding NotificationRepositoryInterface)
- `routes/api.php` (notification routes)
- `docs/project-status.md`, `docs/module-index.md`, `docs/SESSION_HANDOVER.md`

## Future / Not Implemented
- Follow notification: membutuhkan Module 13 (Following System) sebagai dependency — TIDAK dibuat fake, dicatat saja.
- Real-time (Pusher/websocket) & email notification: di luar scope in-app; bisa ditambahkan di Module 18/19.
- Kolom `updated_at` sengaja tidak ada (notification hanya read_at yang berubah).

## Test Result (aktual)
```
php artisan test tests/Feature/NotificationSystemTest.php
→ 13 passed (54 assertions)

php artisan test
→ 214 passed (1047 assertions), 0 failed
```
