# Module 03: Homepage Feed - Design Specification

**Status**: ✅ Approved & Implemented  
**Author**: AI Assistant  
**Date**: 2026-08-06

---

## 1. Module Overview

### Purpose
Menampilkan konten berita berkualitas tinggi secara cepat dan scalable untuk jutaan user. Homepage adalah halaman pertama yang dilihat user, sehingga menjadi critical path untuk retention dan engagement.

### Scope
- Feed utama (all posts)
- Hero Headlines
- Trending News
- Latest News
- Recommended For You
- Categories / Popular Topics
- Editors Pick
- Advertisement placeholder
- Infinite scroll / Load more
- Caching strategy

### Out of Scope (modul lain)
- Post submission (Module 05)
- Voting (Module 07)
- Comments (Module 08)
- Bookmark (Module 09)
- Search (Module 11)
- Following/Subscription feed pribadi (Module 13)

---

## 2. Homepage Layout

### 2.1 Section Order (Mobile-first)
```
┌─────────────────────────────────────────┐
│  1. Header (logo, search, user avatar)  │
├─────────────────────────────────────────┤
│  2. HERO HEADLINES (1 besar + 4 kecil)  │
├─────────────────────────────────────────┤
│  3. Categories chips (horizontal scroll)│
├─────────────────────────────────────────┤
│  4. TRENDING NOW (horizontal cards)     │
├─────────────────────────────────────────┤
│  5. Advertisement (banner placeholder)  │
├─────────────────────────────────────────┤
│  6. LATEST NEWS (vertical list)         │
│     - default tab / main feed           │
│     - tab switch: Hot | New | Top       │
├─────────────────────────────────────────┤
│  7. RECOMMENDED FOR YOU (card grid)     │
├─────────────────────────────────────────┤
│  8. POPULAR TOPICS (list)               │
├─────────────────────────────────────────┤
│  9. EDITORS PICK (compact list)         │
├─────────────────────────────────────────┤
│  10. Load More / Infinite Scroll        │
├─────────────────────────────────────────┤
│  11. Footer                             │
└─────────────────────────────────────────┘
```

### 2.2 Desktop Layout (≥1024px)
```
┌───────────────────────────┬─────────────────────────┐
│  LEFT SIDEBAR (280px)     │  MAIN CONTENT (flex)    │
│  - Logo                   │  - Hero Headlines       │
│  - Categories (vertical)  │  - Trending             │
│  - Popular Topics         │  - Latest News (feed)   │
│  - Editors Pick           │  - Recommended grid     │
│  - Ad placeholder         │  - Infinite scroll      │
├───────────────────────────┴─────────────────────────┤
│  FOOTER                                            │
└─────────────────────────────────────────────────────┘
```
- **Left sidebar**: sticky, persistent navigation
- **Main content**: max-width ~720px untuk readability
- **Right rail (opsional, >1440px)**: ad + trending

### 2.3 Tablet Layout (768-1023px)
```
┌──────────────────────────────┐
│  Header                      │
│  - Hamburger menu            │
│  - Logo centered             │
│  - Avatar                    │
├──────────────────────────────┤
│  Hero (1 besar + 2 kecil)    │
├──────────────────────────────┤
│  Trending (horizontal)       │
├──────────────────────────────┤
│  Latest News (list)          │
├──────────────────────────────┤
│  Recommended (2-col grid)    │
├──────────────────────────────┤
│  Ad + Editors Pick           │
└──────────────────────────────┘
```
- Sidebar collapse ke drawer menu
- Grid 2 kolom untuk recommended

### 2.4 Mobile Layout (<768px)
```
┌──────────────────────────────┐
│  Sticky Header               │
│  - Hamburger | Logo | Search │
├──────────────────────────────┤
│  Categories chips (h-scroll) │
├──────────────────────────────┤
│  Hero (1 besar)              │
├──────────────────────────────┤
│  Trending (h-scroll cards)   │
├──────────────────────────────┤
│  Ad (banner)                 │
├──────────────────────────────┤
│  Latest News (full list)     │
│  - tab: Hot | New | Top      │
├──────────────────────────────┤
│  Recommended (1-col)         │
├──────────────────────────────┤
│  Load More / Infinite        │
└──────────────────────────────┘
```
- Single column, touch-friendly (min 48px hit area)
- Bottom tab bar: Home, Categories, Saved, Profile

---

## 3. Component Hierarchy

```
HomePage
└── HomeFeed
    ├── HeroHeadlines
    │   └── HeroCard
    ├── CategoryChips
    │   └── CategoryChip
    ├── TrendingSection
    │   ├── SectionHeader
    │   └── TrendingCard (horizontal scroll)
    ├── AdBanner (placeholder)
    ├── LatestFeed
    │   ├── FeedTabs (Hot | New | Top)
    │   ├── PostCard
    │   │   ├── PostThumbnail
    │   │   ├── PostContent
    │   │   │   ├── PostMeta (topic, time, author)
    │   │   │   ├── PostTitle
    │   │   │   └── PostExcerpt
    │   │   └── PostStats (votes, comments)
    │   ├── SkeletonPostCard
    │   ├── EmptyState
    │   ├── ErrorState
    │   └── LoadMoreTrigger (IntersectionObserver)
    ├── RecommendedSection
    │   └── PostCardGrid
    ├── PopularTopics
    │   └── TopicRow
    ├── EditorsPick
    │   └── CompactPostCard
    └── Footer
```

---

## 4. User Interaction Flow

```
User opens homepage
    ↓
Initial render: skeleton loading (200ms min)
    ↓
Fetch /api/v1/feed (hero, trending, latest page 1, recommended, topics, editors)
    ↓
Render sections progressively (top-down)
    ↓
User scrolls
    ├── IntersectionObserver fires on LoadMoreTrigger
    │       ↓
    │   Fetch /api/v1/feed?page=2 (append)
    │       ↓
    │   Append cards, update "load more" position
    └── User taps tab (Hot | New | Top)
            ↓
        Reset feed, fetch /api/v1/feed?sort=hot
            ↓
        Replace list (cache-aware)
User taps post → navigate to detail (Module 06)
User taps topic → navigate to topic page (Module 04)
```

---

## 5. States

### 5.1 Loading State
- **First load**: full skeleton (hero + 6 post cards)
- **Tab switch**: skeleton replace list, keep hero/trending
- **Load more**: bottom spinner inline

### 5.2 Empty State
Tampil saat section tidak punya data:
- Icon + "Belum ada berita" / "No posts yet"
- CTA: "Jadilah yang pertama membuat posting"
- Topic kosong: "Topik belum tersedia"

### 5.3 Error State
- **Network error**: tombol "Coba lagi" (retry)
- **Partial failure**: section error tidak blokir halaman lain
- **429 Rate limit**: pesan "Terlalu banyak permintaan" + retry countdown

### 5.4 Skeleton Loading
- Shimmer effect (animated gradient)
- Sesuaikan dimensi komponen target:
  - Hero: 100% x 200px
  - PostCard: thumb 120x90 + 3 baris teks
  - Trending: horizontal cards 200x140

---

## 6. Infinite Scroll Behavior

- Trigger: IntersectionObserver pada sentinel `<div>` di bawah list
- Threshold: 200px sebelum bottom
- Debounce: 300ms setelah threshold terlampaui
- Guard: `isFetching` flag mencegah double-request
- Error: berhenti auto-load, tampil tombol "Muat ulang" manual
- Status bar: "Memuat..." → "Memuat lebih banyak..." → "Tidak ada lagi"

---

## 7. Pagination Strategy

### API
- Page-based (`?page=2&per_page=20`) untuk feed utama
- `per_page` default 20, max 100
- Response menyertakan `meta.pagination` (link-based dari API standard)

### Cache-aware pagination
- Page 1 dicache (TTL pendek: 60-120s)
- Page 2+ tidak dicache (fraud/duplicate risk rendah, tetap query DB)
- Cursor alternatif (`?cursor=`) dipertimbangkan untuk Module 19 (Performance) jika feed > 100k posts

### Frontend state
- `page`, `hasMore`, `items` disimpan di store
- Tab switch mereset state ke page 1

---

## 8. Performance Optimization Strategy

### Backend
1. **Query optimization**
   - Eager loading: `user`, `topic` (hindari N+1)
   - Select columns spesifik (no `content` di list)
   - Index: `published_at`, `hot_score`, `vote_score`, `topic_id+score`
2. **Caching**
   - Feed section cache per-key (Redis recommended)
   - Cache invalidation: hapus key feed saat post baru
3. **Rate limiting**
   - Unauthenticated: 30/min, Authenticated: 60/min (sesuai API standard)
4. **Pagination**
   - Page-based dengan LIMIT/OFFSET yang terindeks

### Frontend
1. **Code splitting**: section lazy-loaded
2. **Image optimization**: `thumbnail_url` resolusi kecil untuk list, full untuk detail
3. **Virtualized list** jika > 50 item (opsional)
4. **Prefetch** page 2 saat user mendekati bottom
5. **HTTP caching**: `Cache-Control` header pada response

---

## 9. SEO Consideration

- **SSR/Prerendering**: halaman utama harus server-rendered atau prerendered untuk crawler
- **Meta tags**: title, description, OG tags (og:title, og:image, og:url)
- **Structured data**: JSON-LD `NewsArticle` / `ItemList`
- **Canonical URL**: `https://laranews.com/`
- **Semantic HTML**: `<article>`, `<h1>` hanya satu, `<nav>`, `<aside>`
- **XML Sitemap**: include homepage (Module 19/20)

---

## 10. Accessibility Consideration

- **WCAG 2.1 AA target**
- Semantic HTML + ARIA roles (nav, main, complementary)
- Keyboard navigation penuh
- Focus indicator terlihat
- `aria-live` untuk feed updates
- Skip link "Langsung ke konten"
- Alt text pada semua gambar
- Kontras warna ≥ 4.5:1
- Reduced motion: disable shimmer jika `prefers-reduced-motion`
- Touch target ≥ 48px
- Font scalable (rem)

---

## 11. Feed Structure Detail

### 11.1 Hero Headlines
- **Kriteria**: priority ladder — 1. Manual Featured (`featured_at`), 2. Breaking News (`is_pinned`), 3. Trending (`hot_score > 0`), 4. Latest (`published_at`)
- **Layout**: 1 post besar (headline) + 4 post compact (secondary)
- **API**: `GET /api/v1/feed/hero`

### 11.2 Trending News
- **Kriteria**: `hot_score` tertinggi dalam 24 jam, maks 10
- **Algoritma**: hot = (votes) / time-decay (lihat section 13)
- **API**: `GET /api/v1/trending`

### 11.3 Latest News
- **Kriteria**: `published_at` terbaru (default view)
- **Tab**: Hot | New | Top (sort switch)
- **API**: `GET /api/v1/feed?sort=hot|new|top&page=N`

### 11.4 Recommended For You
- **Kriteria (v1 non-personalized)**: gabungan Trending + Latest + Popular (top vote) + Editors Pick, di-dedupe, maks 6
- **Kriteria (v2 personalized - Module 13)**: berdasarkan topic subscription user + riwayat baca + AI
- **API**: `GET /api/v1/feed/recommended`

### 11.5 Categories / Popular Topics
- **Kriteria**: topic aktif dengan `post_count` terbanyak, maks 10
- **API**: `GET /api/v1/feed/categories` (reuse topics)

### 11.6 Editors Pick
- **Kriteria**: post dengan flag `featured_at` yang dipilih editor, maks 5
- **API**: `GET /api/v1/feed/editors-picks`

### 11.7 Advertisement Placeholder
- **v1**: placeholder kosong (data kosong / `is_ad: false`)
- **v2**: endpoint `GET /api/v1/ads?placement=home_banner` (Module 15/18)
- Tidak block layout, lazy render

---

## 12. API Design

Semua endpoint berada di bawah resource Feed (`/api/v1/feed/*`), sesuai approval:

### 12.1 `GET /api/v1/feed` (Feed utama + latest)
**Request**
```
GET /api/v1/feed?sort=hot&time=day&topic_id=1&page=1&per_page=20
```
- `sort`: `hot` | `new` | `top` (default `new`)
- `time`: `hour` | `day` | `week` | `month` | `year` | `all` (default `all`)
- `topic_id`: filter by topic
- `page`, `per_page` (default 20, max 100)

**Response (200)**
```json
{
  "status": "success",
  "data": {
    "posts": [
      {
        "id": 123,
        "title": "...",
        "excerpt": "...",
        "post_type": "text",
        "thumbnail_url": null,
        "vote_score": 45,
        "comment_count": 12,
        "is_featured": false,
        "published_at": "2026-08-05T10:00:00Z",
        "user": { "id": 1, "username": "johndoe", "avatar": null },
        "topic": { "id": 1, "name": "Laravel", "slug": "laravel" }
      }
    ]
  },
  "meta": {
    "pagination": {
      "total": 100, "count": 20, "per_page": 20,
      "current_page": 1, "total_pages": 5,
      "links": { "first": "...", "last": "...", "prev": null, "next": "..." }
    },
    "timestamp": "2026-08-06T07:00:00Z"
  }
}
```

**Cache**: key `feed:{sort}:{time}:{topic}:{page}:{per_page}`, TTL 60s

### 12.2 `GET /api/v1/feed/hero`
**Request**: `GET /api/v1/feed/hero`
**Response (200)**
```json
{
  "status": "success",
  "data": {
    "headline": { "id": 500, "title": "...", "image_url": "...", "user": {}, "topic": {} },
    "secondary": [ { "id": 501, "title": "..." }, ... ]
  },
  "meta": { "timestamp": "..." }
}
```
**Cache**: key `feed:hero`, TTL 300s

### 12.3 `GET /api/v1/feed/trending`
**Request**: `GET /api/v1/feed/trending?time=day`
**Response (200)**
```json
{
  "status": "success",
  "data": {
    "trending": [ { "id": 700, "title": "...", "vote_score": 45, ... }, ... ]
  },
  "meta": { "timestamp": "..." }
}
```
**Cache**: key `feed:trending:{time}`, TTL 120s

### 12.4 `GET /api/v1/feed/recommended`
**Request**: `GET /api/v1/feed/recommended` (auth optional)
**Response (200)**: data `posts` array, maks 6 item
**Cache**: key `feed:recommended`, TTL 300s

### 12.5 `GET /api/v1/feed/categories`
**Request**: `GET /api/v1/feed/categories`
**Response (200)**
```json
{
  "status": "success",
  "data": {
    "categories": [
      { "id": 1, "name": "Laravel", "slug": "laravel", "post_count": 1230, "icon": null }
    ]
  },
  "meta": { "timestamp": "..." }
}
```
**Cache**: key `feed:categories`, TTL 600s

### 12.6 `GET /api/v1/feed/editors-picks`
**Request**: `GET /api/v1/feed/editors-picks`
**Response (200)**: data `posts` array, maks 5 item
**Cache**: key `feed:editors-picks`, TTL 600s

**Rate limiting**: semua endpoint feed memakai limiter `feed` (unauthenticated 30/min, authenticated 60/min).

---

## 13. Hot Score Algorithm (Approved)

```
hot_score = views + (comments * 3) + (likes/upvotes * 2) + (shares * 5) - age_decay
```

- `age_decay = (age_hours + 1) ^ 1.8`
- Implementasi di `HotScoreService::calculate()` (app/Services/HotScoreService.php)
- Dipanggil saat post dipublish (`recalculate()`) & saat vote berubah (Module 07)
- Disimpan di kolom `hot_score`
- **Catatan**: kolom `share_count` belum ada di tabel posts (share tracking belum dibangun). Bobot `SHARE_WEIGHT = 5` sudah disiapkan; saat fitur share dibangun, cukup menambahkan kolom `share_count` dan `getShareCount()` akan membacanya — formula tidak berubah.

## 13.1 Hero Priority (Approved)
1. Manual Featured (`featured_at IS NOT NULL`, terbaru)
2. Breaking News (`is_pinned = true`, terbaru) — mekanisme breaking sementara sampai fitur breaking news dedicated dibangun
3. Trending (`hot_score > 0`, tertinggi)
4. Latest (`published_at`, terbaru)

---

## 14. Database Review — ✅ APPROVED

### 14.1 Tabel Existing
| Tabel | Kolom feed | Status |
|-------|-----------|--------|
| `topics` | `slug`, `post_count`, `subscriber_count`, `is_active` | ✅ Cukup |
| `posts` | `hot_score`, `vote_score`, `published_at`, `last_activity_at`, `is_pinned` | ✅ Cukup |

### 14.2 Perubahan yang Disetujui: `posts.featured_at` + `posts.featured_by`

**Apa**: Tambah kolom pada tabel `posts`:
- `featured_at TIMESTAMP NULL` — waktu editor menandai post sebagai pilihan editor (Editors Pick / Hero)
- `featured_by BIGINT UNSIGNED NULL` — FK ke `users` untuk audit siapa editor yang menandai

**Kenapa**: Section **Editors Pick** dan **Hero Headlines** membutuhkan penanda konten pilihan editor + timestamp (bisa dijadwalkan & disorting by waktu) + audit trail.

**Impact**:
- Migration additive (tidak mengubah data existing)
- `featured_by` nullable + `nullOnDelete` (jika editor dihapus, feature tetap tersimpan tanpa auditor)
- Backward compatible

**Migration plan**:
```php
Schema::table('posts', function (Blueprint $table) {
    $table->timestamp('featured_at')->nullable();
    $table->foreignId('featured_by')->nullable()->constrained('users')->nullOnDelete();
});
```
> ✅ Implemented langsung di `2026_08_06_152620_create_posts_table.php` (migration masih pending saat approval, jadi tanpa ALTER terpisah).

### 14.3 Tidak Perlu Diubah (Untuk Sekarang)
- `topic_subscriptions` → diperlukan untuk "Recommended" personal, tapi ini **Module 13**. v1 recommended pakai fallback global.
- `post_views` per-user → Module 19 (Analytics).
- Redis index → Module 19.

---

## 15. Dependencies — ❌ Redis DITUNDA (Approved)

### 15.1 Redis (Cache + Queue) — DITUNDA ke fase Deployment

**Keputusan**: Gunakan **Laravel Database Cache** untuk Module 1-10. Redis diimplementasikan di Module 11-15, dan Redis + Queue saat production.

**Alasan**:
- Project masih di awal (Module 3), belum ada traffic/production/deploy
- Install Redis sekarang hanya menambah complexity
- Arsitektur sudah Redis-ready: `config/cache.php` & `config/queue.php` sudah support Redis, migrasi nanti hanya ubah `CACHE_STORE` & `QUEUE_CONNECTION` di `.env` tanpa mengubah business logic

**Feed cache strategy (database cache)**:
- `feed:*` TTL 60s
- `feed:hero` TTL 300s
- `feed:trending:*` TTL 120s
- `feed:recommended` TTL 300s
- `feed:categories` & `feed:editors-picks` TTL 600s

### 15.2 Tanpa Dependency Tambahan
- Tidak butuh Meilisearch/Elasticsearch (Search = Module 11)
- Tidak butuh package baru untuk feed (Laravel native)
- Tidak butuh CDN/Storage untuk v1

---

## 16. Implementation Order (setelah approval)

1. ✅ Migration: `posts.featured_at` + `posts.featured_by`
2. ✅ Models: `Post`, `Topic`
3. ✅ Factory: `PostFactory`, `TopicFactory`
4. ✅ Seeder: topics + sample posts (dev)
5. ✅ Service: `FeedService`, `HotScoreService`
6. ✅ Repository: `PostRepository` + `PostRepositoryInterface`
7. ✅ Controllers: `FeedController`
8. ✅ API Resources: `PostResource`, `TopicResource`
9. ✅ Validation: `FeedRequest`
10. ✅ Routes: 6 endpoint feed
11. ✅ Tests: FeedTest, FeedSectionsTest, HotScoreServiceTest
12. ✅ Documentation: update project-status, module-index, SESSION_HANDOVER

---

## 17. Deliverables Summary

- **Files created**: 14
  - `app/Services/FeedService.php`
  - `app/Services/HotScoreService.php`
  - `app/Repositories/PostRepositoryInterface.php`
  - `app/Repositories/PostRepository.php`
  - `app/Http/Controllers/Api/V1/FeedController.php`
  - `app/Http/Requests/Api/V1/FeedRequest.php`
  - `app/Http/Resources/Api/V1/PostResource.php`
  - `app/Http/Resources/Api/V1/TopicResource.php`
  - `database/migrations/2026_08_06_152500_create_topics_table.php`
  - `database/migrations/2026_08_06_152620_create_posts_table.php`
  - `database/factories/PostFactory.php`, `database/factories/TopicFactory.php`
  - `database/seeders/TopicSeeder.php`, `database/seeders/PostSeeder.php`
- **Files modified**: `routes/api.php`, `app/Models/Post.php`, `app/Models/Topic.php`, `app/Providers/AppServiceProvider.php`, `database/seeders/DatabaseSeeder.php`
- **Test target**: 26 test cases (Feed 13, FeedSections 11, HotScore 4)
- **Progress**: Module 03 = 100% (backend feed)

---

## 18. Remaining Tasks (di luar scope Module 03)

1. Frontend rendering (component hierarchy, skeleton, infinite scroll) — membutuhkan frontend stack (belum didefinisikan)
2. Personalisasi Recommended (Module 13 + AI)
3. Feature/unfeature endpoint untuk editor (Module 15 Admin Panel)
4. Cache invalidation saat post baru dipublish (Module 05)
5. Breaking News flag dedicated
