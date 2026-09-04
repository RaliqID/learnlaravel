# UI-01 — Homepage / Feed (LaraNews Frontend)

## Status
✅ **VERIFIED & COMPLETE (2026-08-10)** — setelah FIX + RE-AUDIT

## Frontend Stack
- Laravel Blade (server-rendered components) + Tailwind CSS v4 + Vite 7
- JavaScript: vanilla ES modules (no framework — sesuai stack yang ditemukan saat audit)
- API: centralized `resources/js/api/client.js`

## Audit yang Dilakukan
Audit ulang dilakukan berbasis **source code aktual** (bukan laporan sebelumnya).

### File yang DIVERIFIKASI ADA
| File | Fungsi |
|------|--------|
| `resources/js/api/client.js` | Centralized API layer — endpoint verified |
| `resources/js/home.js` | Homepage controller (hero/trending/feed/recommended/categories/editors) |
| `resources/js/app.js` | Entry point (bootstrap + initHomepage) |
| `resources/views/pages/home.blade.php` | Homepage Blade (struktur spec) |
| `resources/views/components/layout/app.blade.php` | AppShell layout (header+main+footer) |
| `resources/views/components/ui/*` | button, card, skeleton, empty-state, icon, dll |
| `resources/css/tokens.css` | Design tokens |
| `resources/css/app.css` | Tailwind v4 entry |

### File yang TIDAK ADA (halusinasi pada laporan sebelumnya)
- `resources/js/ui.plugin.js` — TIDAK ADA
- `resources/views/partials/metadata.blade.php` — TIDAK ADA
- CSS class `card--compact`, `border-accent-accent` — TIDAK ADA
- Teks "Mercury box", "JSONL", "Bangladesh", "ticket links", "Shipp" — TIDAK ADA

## API Contract (verified via `php artisan route:list` + HTTP 200)

| Feature | Actual Endpoint | Method | Controller | Response Shape |
|---------|-----------------|--------|------------|----------------|
| Hero | `/api/v1/feed/hero` | GET | FeedController@hero | `{data:{headline?, secondary[]}}` |
| Trending | `/api/v1/feed/trending` | GET | FeedController@trending | `{data:{trending[]}}` |
| Feed | `/api/v1/feed` | GET | FeedController@index | `{data:{posts[]}, meta:{pagination}}` |
| Recommended | `/api/v1/feed/recommended` | GET | FeedController@recommended | `{data:{posts[]}}` |
| Categories | `/api/v1/feed/categories` | GET | FeedController@categories | `{data:{categories[]}}` |
| Editor's Pick | `/api/v1/feed/editors-picks` | GET | FeedController@editorsPicks | `{data:{posts[]}}` |

> Catatan: Prompt awal menulis `/api/v1/hero`, `/api/v1/trending`, `/api/v1/recommended`, `/api/v1/editors-pick`. **Route tersebut TIDAK ada di backend.** Endpoint aktual semua berada di bawah prefix `/feed` (arsitektur Module 03). Frontend menggunakan endpoint aktual — tidak membuat route baru, tidak mengubah backend. ✅

## Frontend Data Flow
```
GET /
  → layouts/app.blade.php (AppShell)
  → pages/home.blade.php (skeleton + container per section)
  → app.js → home.js
      ├── feedApi.hero()        → renderHero  (#home-hero)
      ├── feedApi.trending()    → renderTrending (#home-trending)
      ├── feedApi.feed()        → initFeed tabs+infinite (#feed-posts)
      ├── feedApi.recommended() → renderRecommended (#home-recommended)
      ├── feedApi.categories()  → renderCategories (#home-categories)
      └── feedApi.editorsPicks()→ renderEditors (#home-editors)
  → postCard() build DOM (article + semantic link)
```

Fields yang dipakai hanya yang tersedia di `PostResource` (verified):
`id, title, slug, excerpt, image_url, thumbnail_url, vote_score, comment_count, view_count, is_pinned, is_featured, published_at, user{username,display_name}, topic{name,slug}`.

**`reading_time` TIDAK ada di PostResource** → tidak dirender (tidak dipaksa). Field `featured_at`/`is_featured` tidak dimanfaatkan untuk hero karena backend hero endpoint sudah menyediakan struktur headline+secondary.

## Infinite Scroll (feed only)
```
page 1
 ↓
IntersectionObserver (rootMargin 200px, sentinel #feed-sentinel)
 ↓
page 2 … page N
 ↓
page > last_page (meta.pagination.last_page)
 ↓
observer.disconnect() → stop
```
- Hanya feed yang punya infinite scroll; hero/trending/recommended/categories/editors request sekali.
- `loading` flag mencegah concurrent/duplicate request.
- Error → inline error state + tombol Retry (`data-retry="feed"`).
- Observer disconnect saat `done`.

## Tab (New / Hot / Top)
- Tiga tab async: `sort=new|hot|top` dikirim ke `/feed`.
- Tidak full-page reload; skeleton saat switch.
- `aria-selected`, `role=tablist`, `role=tab`.

## Loading / Empty / Error
- Skeleton server-side di Blade + skeleton feed (JS) saat ganti tab.
- Empty state: "No stories yet", "No categories yet", dll.
- Error inline: "Couldn't load …" + Retry; section lain tetap render.

## Responsive
- Hero: desktop `lg:grid-cols-3` (2-col large + 1-col small); mobile stack.
- Feed/recommended: `sm:grid-cols-2 lg:grid-cols-3` → 1-col mobile.
- Breakpoints sesuai tokens (`sm/md/lg/xl`). No fixed-width overflow.

## Accessibility
- Semantic `<header>/<main>/<footer>/<article>/<nav>`
- `h1` (Latest stories) → `h2` per section → heading hierarchy
- Tablist ARIA; dropdown ARIA; focus-visible outline; `aria-label` icon buttons
- `prefers-reduced-motion` (Tailwind base)
- Skeleton `aria-hidden`

## SEO
- `<title>` "Home — LaraNews"
- meta description, canonical (`url()->current()`), OG 5 tags + twitter card
- favicon (public/favicon.ico) di-link layout

## Verification (actual commands)
```
npm run build
→ vite v7.3.6 building; 58 modules transformed; app-*.js 58.96 kB (gzip 21.65 kB); ✓ built

php artisan test
→ 371 passed, 0 failed, 1553 assertions

php artisan route:list → home route ada; feed/hero/trending/recommended/categories/editors-picks 200
HTTP GET / → 200; homepage-root, logo, feed-posts, tabs, sentinel, ad placeholder, skeleton present
```

## Files
### Created
- `resources/js/home.js`
- `docs/modules/ui-01-homepage.md`

### Modified
- `resources/js/api/client.js` — feedApi lengkap (feed/hero/trending/recommended/categories/editorsPicks), nama jelas, no alias
- `resources/js/app.js` — entry, import home.js
- `resources/views/pages/home.blade.php` — struktur spec penuh (hero→trending→feed→recommended→categories→editors→ad)
- `resources/views/components/layout/app.blade.php` — canonical + OG tags (dipindah dari `layouts/app.blade.php`)
- `resources/css/styles.css` — hapus `.prose-editorial` (dead code, theme() invalid di v4)
- `routes/web.php` — tidak berubah (route `/` sudah ada)

### Moved
- `resources/views/layouts/app.blade.php` → `resources/views/components/layout/app.blade.php` (agar `x-layout.app` ter-resolve; konsisten dengan `components/layout/header|footer`)

## Bugs Found & Fixed
| Severity | File | Problem | Root Cause | Fix |
|----------|------|---------|-----------|-----|
| Critical | `layouts/app.blade.php` | `x-layouts.app` tidak resolve → homepage 500 (ExampleTest, PostVoting, CommentVoting) | Blade component namespace salah (layout berada di `layouts/` bukan `components/layouts/`) | Pindah ke `components/layout/app.blade.php` + gunakan `x-layout.app` |
| High | `home.js` | Build failed `Could not resolve "../api/client.js"` | Path import salah (home.js di `resources/js/`) | `./api/client.js` |
| Medium | `styles.css` | `.prose-editorial` + `theme(colors.accent)` tidak dipakai & invalid di Tailwind v4 | dead code | dihapus |
| Medium | `home.blade.php` | Tidak ada `<h1>` (heading hierarchy) | homepage belum punya heading utama | feed heading → `<h1>`, sections lain `<h2>` |
| Low | `app.blade.php` | Tidak ada canonical/OG | spec SEO | tambah canonical + OG + twitter card |

## Known Issues
- `reading_time` tidak tersedia di `PostResource` → tidak dirender (bukan bug; sesuai contract backend).
- Hero/trending/recommended request tidak di-cache client-side (tiap load homepage memanggil API lagi). Backend feed sudah cache server-side; untuk scale bisa ditambah di UI performance pass.
- Tidak ada og:image (posting image per artikel belum ditambahkan) — minor.

## Next Recommended UI Module
**UI-02 — Post Detail / Reading Experience** (butuh `PostDetailResource` — reading_time tersedia di detail endpoint). Menunggu instruksi.
