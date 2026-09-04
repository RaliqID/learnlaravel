# LaraNews

LaraNews — Automated tech & finance news aggregator and community platform built with Laravel 12, ingesting live financial news from the DataSectors API, with topic classification, voting, comments, bookmarks, chat, notifications, and a full admin/moderation suite.

## Features

- **Automated news ingestion** — scheduled fetch every 30 minutes from the DataSectors News API with canonical dedupe, keyword + market-type topic classification, and automatic image resolution
- **Community features** — user posts, up/down voting, threaded comments, comment voting, bookmarks, follows, and real-time-ish chat
- **Feed engine** — hero, trending (velocity + hot score), recommended, editor's picks, topic sections, category distribution, and activity charts
- **Search** — full-text search across posts, topics, and users (MySQL boolean mode)
- **Auth** — email/password with verification and password reset, plus Google OAuth (browser flow via token fragment)
- **Notifications & subscriptions** — topic subscriptions with notification preferences
- **Moderation & admin panel** — reports, post/comment removal, user bans, role management, and dashboard analytics
- **Rate limiting** — per-endpoint throttles across auth, feed, and mutation routes

## Tech Stack

- **Backend:** Laravel 12 (PHP 8.3), Laravel Sanctum (API auth), Laravel Socialite (Google OAuth)
- **Database:** MySQL 8 (full-text search, soft deletes)
- **Frontend:** Blade + Vite
- **Testing:** PHPUnit (371 tests, 1553 assertions)

## Installation

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan news:fetch   # populate news from DataSectors API
php artisan serve
```

## Environment

Key variables (see `.env.example`):

```
DB_CONNECTION=mysql
DATASECTORS_API_KEY=      # DataSectors news API key
GOOGLE_CLIENT_ID=         # Google OAuth
UNSPLASH_ACCESS_KEY=      # image fallback resolver
```

## Usage

- News ingestion runs via scheduler: `php artisan schedule:work` (fetches every 30 minutes)
- Manual fetch: `php artisan news:fetch` (DataSectors API, default) or `php artisan news:fetch --provider=rss` (legacy RSS fallback)
- API base: `/api/v1/` — feed, topics, posts, votes, comments, bookmarks, notifications, search, moderation, admin

## Testing

```bash
php artisan test
```

## License

MIT

**Author:** Raliq Hidayat BM3
