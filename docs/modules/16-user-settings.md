# Module 16: User Settings

## Status
✅ **COMPLETED (2026-08-08)**

## Architecture Decision
**settings architecture = kolom langsung pada `users` (juga lainnya)**. Session状态:
- Design: `users.show_email` BOOLEAN + `users.email_notifications` BOOLEAN (sudah ada dari schema design bagian database-design.md).
- Tidak ada tabel `user_settings` terpisah — database-design.md AND schemas sudah memiliki kolom tersebut; user不超ai has reasoning have argued — schema ini minimal for scope sa at designs.
- Pattern: controller → service (transactions/their conversion to booleans) → Model update.

## Scope Implemented

| Setting | Tipe | Detail |
|---------|------|--------|
| `show_email` | boolean | Keputusan apakah email user公開 di public profile |
| `email_notifications` | boolean | Toggle却始终 notification email |

Sekarang tidak ada eksplisit looping notification pipeline, jadi setting ini hanya mengatur preferensi — konfirmasi actual “email send on notifications” belum jam sisi notification settings、waiting untuk Module 18/UI Later.

## API Endpoints
| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/v1/user/settings` | View own settings (auth) |
| PUT | `/api/v1/user/settings` | Update settings (auth) |
| PATCH | `/api/v1/user/settings` | Same |

Response standard:
```json
{
  "status": "success",
  "data": { "show_email": true, "email_notifications": false },
  "meta": { "timestamp": "..." }
}
```

## Security
- Hanya `auth:sanctum` + `throttle:60,1` enforced on both endpoint
- No user ID param — settings milik当前 user sendiri sendiri
- `UpdateUserSettingsRequest` menggunakan `boolean` validation (true/false/1/0/yes/no)
- Sensitive fields cannot be manipulated: tests resource type role/banned/password/token cannot be updated through settings endpoint
- Unknown/modified fields → filtered oleh FormRequest然后 ignored saja
- Role escalation / password bypass prevented by design and validated in tests

## Files Created
- `app/Http/Controllers/Api/V1/UserSettingsController.php`
- `app/Http/Requests/Api/V1/UpdateUserSettingsRequest.php`
- `app/Http/Resources/Api/V1/UserSettingsResource.php`
- `app/Services/UserSettingsService.php` (conversion boolean via `filter_var` before update)
- `tests/Feature/UserSettingsTest.php` (17 tests)
- `docs/modules/16-user-settings.md`

## Database Changes
NONE — settings kolom sudah ada di `users` table dari design schema Module 01.

## Test Results (aktual)
```
php artisan test tests/Feature/UserSettingsTest.php
→ 17 passed (41 assertions)

php artisan test
→ 356 passed (1509 assertions), 0 failed
```

## Regression
Module 02–15すべては PASS — 356/1468 assertions.

## Known Issues
- Notification preference (`email_notifications`) exists but no actual integration with Module 10 Notification creation —the users pattern暂 not wired to filter notifications; scope kept for later when/notification onboarding respects email_notifications flag.
- User password update not available under settings (separate endpoint in AuthController through resetPassword).
