# Module 15: Admin Panel

## Status
✅ **COMPLETED (2026-08-08)**

## Overview
Admin Panel backend for LaraNews — dashboard stats aggregate, user management (list/search/filter/detail/role update), dan integration dengan existing moderation system. Semua di server-side API — tanpa frontend.

## API Endpoints
| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/v1/admin/dashboard` | Aggregate stats users/posts/comments/reports |
| GET | `/api/v1/admin/users` | List users — filter role/banned, search (username/display_name/email), pagination |
| GET | `/api/v1/admin/users/{user:username}` | User detail (data publik + follower count) |
| PUT | `/api/v1/admin/users/{user:username}/role` | Update role (admin only, UserPolicy::updateRole prevents self) |

All Auth'ed — admin-only via Gate `admin-area` → guest → 401, moderator/regular → 403, admin → 200.

## Authorization
| Action | Policy | Logic |
|--------|--------|-------|
| All /admin/* | Gate `admin-area` | `role === 'admin'` |
| updateRole | `UserPolicy::updateRole` + custom Request | admin, tidak bisa diri sendiri |
| all user ops | Admin UserResource | return sensitive fields aman |

## Sensitive Fields Guard
AdminUserResource tidak mengekspos: password, remember_token, tokens, email. Tentang ban status (banned_until, banned_reason) yang memang available karena it's admin panel context (policy data penting untuk moderation).

## Integration Moderation (Module 14)
`/api/v1/admin/dashboard` menyediakan aggregated Reports (open/resolved/dismissed) & recent_moderation_actions dari `moderation_logs` untuk notify admin tentang latest actions.

## Architecture
```
Admin/DashboardController → AdminService (DB queries)
Admin/UserController → AdminService → UserRepository
UpdateUserRoleRequest → UserPolicy::updateRole (gate)
```
- Admin UserList organized by repository — join follows for followers_count
- Filtering role/search/banned; gate `admin-area` via AppServiceProvider
- Dashboard via direct aggregated queries (SELECT SUM CASE) — efficient
- Post removal/restore, comment actions, ban/unban → delegated to ModerationService (Module 14, bukan duplicate logic)

## Files Created
- `app/Repositories/UserRepository.php` + Interface
- `app/Services/AdminService.php`
- `app/Http/Resources/Api/V1/Admin/UserResource.php`
- `app/Http/Controllers/Api/V1/Admin/DashboardController.php`
- `app/Http/Controllers/Api/V1/Admin/UserController.php`
- `app/Http/Requests/Api/V1/Admin/UserListRequest.php`
- `app/Http/Requests/Api/V1/Admin/UpdateUserRoleRequest.php`
- `tests/Feature/AdminPanelTest.php` (22 tests, 79 assertions)
- `docs/modules/15-admin-panel.md`

## Database Changes
NONE — semua query leveraging existing schema dengan LEFT JOIN followers.

## External Dependencies
NONE — tidak ada external storage/service. Tidak ada Human Action Required.

## Test Result (aktual)
```
php artisan test tests/Feature/AdminPanelTest.php
→ 22 passed (79 assertions)

php artisan test
→ 339 passed (1468 assertions), 0 failed
```

## Regression
Module 02–14: **NO REGRESSION** (339 tests all pass).

## Known Issues
- Test expectations pada dashboard menggunakan relative queries daripada absolute count karena data baseline bervariasi dalam RefreshDatabase environment (LRU/lock behavior MySQL)
- Review actual banned logic: service based (permanent = banned_until null); registered in `active` calc.
