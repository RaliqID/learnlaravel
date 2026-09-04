# UI-02 — Post Detail / Reading Experience (LaraNews Frontend)

## Status
✅ **VERIFIED & COMPLETE (2026-08-10)** — termasuk improvement pass (no native prompt/confirm, in-place comment vote, bookmark initial state verification)

## Frontend Stack
- Laravel Blade + Tailwind CSS v4 + Vite 7
- Vanilla ES modules (sesuai architecture UI-01)
- Centralized API client: `resources/js/api/client.js`

## Routes Used
| Route | Type | Fungsi |
|-------|------|--------|
| `GET /posts/{identifier}` | web (new) | Halaman post detail |
| `GET /api/v1/posts/{id\|slug}` | API (existing) | Post detail + related + navigation |

Route API **tidak diubah**; web route baru hanya menyajikan view (client fetch API).

## API Endpoints Used (verified `route:list` + live HTTP 200)
| Feature | Endpoint | Response |
|---------|----------|----------|
| Post detail | `GET /api/v1/posts/{id\|slug}` | `data.post` (PostDetailResource) |
| Related posts | (same response) | `data.related_posts[]` |
| Navigation | (same response) | `data.navigation.{previous,next}` |
| Comments | `GET /api/v1/posts/{post}/comments` | `data[]` + `meta.pagination` |
| Create comment | `POST /api/v1/posts/{post}/comments` | 201 |
| Reply | `POST /api/v1/comments/{comment}/replies` | 201 |
| Edit comment | `PUT /api/v1/comments/{comment}` | 200 |
| Delete comment | `DELETE /api/v1/comments/{comment}` | 200 |
| Comment vote | `POST/DELETE /api/v1/comments/{comment}/vote`, `/vote/down` | 200 |
| Bookmark | `POST/DELETE /api/v1/posts/{post}/bookmark` | 201/200 |
| Post vote | `POST/DELETE /api/v1/posts/{post}/vote`, `/vote/down` | 200 |

## Backend Fields Consumed (verified via PostDetailResource live response)
`id, title, slug, excerpt, content_html, content, reading_time, post_type, image_url, thumbnail_url, meta_title, meta_description, status, is_pinned, is_locked, is_nsfw, is_approved, is_featured, vote_score, upvote_count, downvote_count, comment_count, view_count, bookmark_count, published_at, edited_at, created_at, user{id,username,display_name,avatar}, topic{id,name,slug}`

> **content**: backend menyimpan Markdown → `content_html` dirender server-side (Str::markdown dengan `html_input=strip`, `allow_unsafe_links=false` — disanitasi saat creation Module 05). Frontend merender `content_html` sebagai HTML aman; fallback `content` sebagai plain text escaped.

> **Limitation — bookmark initial state**: `PostDetailResource` TIDAK menyediakan `is_bookmarked` / `user_vote`. State awal di-set "off"/"null"; setelah aksi pertama, UI merefleksikan kebenaran server (tidak ada optimistic state menyesatkan).

## UI Sections Implemented
1. Reading progress bar (scroll-based, `prefers-reduced-motion` aman)
2. Breadcrumb / topic
3. Article header (H1 title, dek, author+avatar, published/reading time)
4. Action toolbar: vote (▲ score ▼), bookmark (Save/Saved), share
5. Hero image (bila `image_url` ada)
6. Article body (`content_html` aman; fallback escaped plain text)
7. Prev/Next navigation (dari `data.navigation`)
8. Related posts (dari `data.related_posts`)
9. Comments (list + create + reply + edit own + delete own + vote + pagination infinite)

## Authentication Behavior
- **Guest**: dapat membaca artikel & comments; tombol vote/bookmark disabled (opacity + `aria-disabled`); CTA "Log in" muncul; comment form diganti login prompt.
- **Authenticated**: vote, bookmark, comment, reply, edit/delete own comment (tombol hanya muncul bila `authId === comment.user.id`).
- Semua authorization ditentukan backend; frontend hanya mencerminkan status.

## Loading / Error / Empty States
- **Loading**: layout skeleton awal; detail & comments dimuat async.
- **Error article**: halaman error "Couldn't load this story" + tombol Back to home.
- **Error comments**: inline "Could not load comments."
- **Empty**: "No comments yet…"; related section disembunyikan bila kosong; nav disimpan bila tidak ada.
- **Toast**: feedback untuk share-copy, comment post/delete, bookmark (via existing `laranewsToast`).

## Accessibility
- Semantic `<article>`, `<header>`, `<nav>`, `<section>`
- Heading: H1 → section H2
- `aria-label` untuk icon-only vote/bookmark/share buttons
- Vote group `role="group"`; vote buttons `aria-disabled` saat guest
- Keyboard: native buttons/links; focus-visible via design system
- `prefers-reduced-motion` (global base)
- Comment textarea punya `<label class="sr-only">`

## SEO
- Dynamic `<title>` = `{post.title} — LaraNews`
- `meta description` ← `meta_description || excerpt`
- canonical = current URL
- OG: title, description, image (jika `image_url`)

## Performance
- Satu request detail per halaman; related/navigation ikut dalam response yang sama (tidak ada fetch tambahan)
- Comments pagination via IntersectionObserver (rootMargin 200px); `loading` flag cegah duplicate
- Hero image eager (`LCP`); tidak lazy-load gambar hero
- Tidak ada dependency baru; tidak ada N+1 dari Blade (data via satu API call)

## Security
- `content_html` berasal dari backend yang sudah sanitize Markdown → tidak ada script
- `content` fallback di-escape (XSS-safe)
- Comment content di-escape saat render (user input plain text)
- Tidak expose token/password/email; `LANews.auth` hanya boolean + id
- Vote/bookmark/comment authorization di backend (frontend tidak mengandalkan)

## Verification (actual commands)
```
npm run build
→ 59 modules transformed; app-*.js 71.39 kB (gzip 24.55 kB); ✓ built in 5.52s

php artisan test
→ 371 passed, 0 failed, 1553 assertions

php artisan route:list
→ GET /posts/{identifier} (web) + API posts/comments/vote/bookmark verified

HTTP GET /posts/7 → 200; progress bar, vote-group, LANews.auth exposed, built JS linked
HTTP GET /api/v1/posts/7 → 200; content_html + reading_time + related + navigation present
```

## Bugs Found & Fixed
| Sev | File | Bug | Fix |
|-----|------|-----|-----|
| Medium | `components/layout/app.blade.php` | `window.LANews.auth`/`authId` belum diset (post.js memerlukannya) | expose via Auth::check()/Auth::id() |
| High | `post.js` | `prompt()`/`confirm()` native untuk reply/edit/delete — tidak profesional, rusak di mobile | inline form (textarea + save/cancel) + inline delete confirm |
| High | `post.js` | Comment vote reload seluruh list (reset scroll/pagination) — boros & MISLEADING scroll jump | patch score in-place dari response `comment_id`+`vote_score` |
| Medium | `post.js` | Guest vote button di-`disabled` → login hint tidak pernah tampil saat click | tidak disabled; click → showLoginHint() |
| Medium | `post.js` | Bookmark initial state diasumsikan "off" tanpa verifikasi | authed user: fetch `GET /bookmarks` sekali untuk cek state |
| Low | `home.js` | Card link pakai `/posts/{id}` (bukan slug) | `postUrl()` helper → slug fallback id |
| Low | layout | `og:image` meta tag tidak ada | tambah `<meta property="og:image">` |
| Low | `client.js` | temporary duplicate `books`+`list` alias | hapus `books` |

## Remaining Issues / Limitations
- `is_bookmarked` & `user_vote` tidak ada di PostDetailResource → state divalidasi via list (bookmark) & neutral (vote) — tidak mengarang
- **Nested replies tidak ditampilkan**: `GET /posts/{post}/comments` hanya return top-level (`whereNull('parent_id')`, verified backend) — UI menampilkan `reply_count`, reply dibuat via `POST /comments/{id}/replies`, list di-refresh → count naik. Nested display membutuhkan enhancement backend (dicatat, bukan dikarang)

## Files Created
- `resources/js/post.js`
- `resources/views/pages/post.blade.php`
- `docs/modules/ui-02-post-detail.md`

## Files Modified
- `routes/web.php` — `GET /posts/{identifier}`
- `resources/js/api/client.js` — postsApi/commentsApi/bookmarkApi/voteApi
- `resources/js/app.js` — entry initPostPage
- `resources/views/components/layout/app.blade.php` — expose auth state

## Next Recommended
**UI-03 — Topics** (menunggu instruksi)
