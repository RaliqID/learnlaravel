# Module 09: Bookmark System

**Status**: ✅ Verified & Complete  
**Date**: 2026-08-08  
**Priority**: MEDIUM  
**Dependencies**: Module 6 (Post Detail)

---

## Purpose

Memungkinkan user menyimpan (bookmark) post untuk dibaca nanti, mengorganisir dalam koleksi (collection), dan mengelola daftar bookmark miliknya.

## Functional Requirements

- FR-BOOKMARK-001: Bookmark post
- FR-BOOKMARK-002: View bookmarks (pagination, filter by collection)
- FR-BOOKMARK-003: Remove bookmark
- FR-BOOKMARK-004: Bookmark counter & statistics (collections)
- FR-BOOKMARK-005: Bookmark context & discovery

## User Actions

| Action | Method | Endpoint |
|--------|--------|----------|
| Bookmark post | POST | `/api/v1/posts/{post}/bookmark` |
| Unbookmark post | DELETE | `/api/v1/posts/{post}/bookmark` |
| List bookmarks | GET | `/api/v1/bookmarks` |
| Remove by id | DELETE | `/api/v1/bookmarks/{bookmark}` |
| List collections | GET | `/api/v1/bookmarks/collections` |

Semua endpoint memerlukan `auth:sanctum`.

## Request Body

```json
{
    "collection_name": "Laravel",
    "notes": "Baca nanti"
}
```

Keduanya opsional. `collection_name` max 100, `notes` max 5000.

## System Response

`POST /api/v1/posts/{post}/bookmark` → **201**
```json
{
    "status": "success",
    "message": "Post bookmarked successfully",
    "data": {
        "id": 1,
        "collection_name": "Laravel",
        "created_at": "...",
        "post": { "id": 1, "title": "...", "slug": "..." }
    }
}
```

`GET /api/v1/bookmarks` → **200** dengan `data` (BookmarkResource) dan `meta.pagination`.

## Validation Rules

- `collection_name`: nullable, string, max 100
- `notes`: nullable, string, max 5000
- `page`/`per_page`: integer, per_page max 100

## Business Rules

- Satu user hanya boleh punya satu bookmark per post (unique `user_id, post_id`)
- Bookmark idempotent: POST berulang memperbarui `collection_name`/`notes`, tidak duplicate
- Unbookmark post yang tidak di-bookmark → idempotent (200, "Post is not bookmarked")
- Draft/unpublished post tidak dapat di-bookmark → 422
- `posts.bookmark_count` sinkron: increment saat bookmark, decrement saat unbookmark (tidak pernah negatif)

## Permission / Authorization

- Bookmark/unbookmark: user authenticated (auth:sanctum)
- Remove by id: hanya pemilik bookmark atau admin (`BookmarkPolicy::delete`)

## Edge Cases

- Guest → 401
- Draft post → 422
- Bookmark sendiri dihapus user lain → 403
- Bookmark duplicate → idempotent update
- Counter tidak boleh negatif

## Error States

- 401 Unauthenticated
- 403 Forbidden (bukan pemilik bookmark)
- 422 Unprocessable (post tidak tersedia untuk bookmark)
- 404 Not Found (post/bookmark tidak ada)

## Dependencies

- Module 6 (Post Detail) — post yang di-bookmark harus published
- Auth Sanctum
- Tabel `bookmarks`

## Database Behaviour

Migration `2026_08_08_061204_create_bookmarks_table`:
- `user_id` FK → users (cascade delete)
- `post_id` FK → posts (cascade delete)
- `collection_name` VARCHAR(100) nullable
- `notes` TEXT nullable
- unique (user_id, post_id)
- index (user_id, collection_name), (created_at)

## API Behaviour

- Versioning `/api/v1`
- Response mengikuti api-standard (status, data, meta.timestamp)
- Pagination mengikuti standard (meta.pagination)

## Security

- Auth Sanctum pada semua endpoint
- Policy ownership untuk remove by id
- Mass assignment terlindungi via `$fillable`
- Parameterized query / Eloquent

## Performance

- Eager loading `post` + `post.topic` pada list
- Pagination untuk list bookmarks
- Index (user_id, collection_name) untuk filter koleksi

## Scalability

- Counter `bookmark_count` denormalized di posts (query statistik cepat)
- Collections dihitung via groupBy (terindex)

## Testing

`tests/Feature/BookmarkTest.php` — 11 test cases, 52 assertions, 100% PASS.
