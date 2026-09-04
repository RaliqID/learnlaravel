# Decision Log

## Overview
Dokumen ini mencatat semua keputusan penting yang diambil selama development project LaraNews. Setiap keputusan didokumentasikan dengan konteks, alasan, dan dampaknya.

## Decision Log Format
- **ID**: Unique identifier (DEC-YYYYMMDD-XXX)
- **Date**: Tanggal keputusan dibuat
- **Category**: Architecture / Database / API / Security / Performance / UI/UX
- **Status**: Proposed / Accepted / Rejected / Superseded
- **Context**: Situasi yang memerlukan keputusan
- **Decision**: Keputusan yang diambil
- **Rationale**: Alasan di balik keputusan
- **Consequences**: Dampak positif dan negatif
- **Alternatives**: Opsi lain yang dipertimbangkan

---

## Active Decisions

### DEC-20260805-001: Monolithic Architecture
**Date**: 2026-08-05  
**Category**: Architecture  
**Status**: Accepted  

**Context**:
Perlu menentukan arsitektur aplikasi antara monolithic vs microservices untuk project LaraNews.

**Decision**:
Menggunakan **Monolithic Modular Architecture** dengan Laravel sebagai framework utama.

**Rationale**:
1. **Simplicity**: Lebih mudah untuk develop dan maintain di early stage
2. **Development Speed**: Faster time to market
3. **Team Size**: Optimal untuk single developer atau small team
4. **Laravel Ecosystem**: Leverage built-in features Laravel
5. **Cost**: Lower infrastructure cost
6. **Debugging**: Easier to debug dan troubleshoot
7. **Future Flexibility**: Dapat di-refactor ke microservices jika diperlukan

**Consequences**:
- ✅ Faster initial development
- ✅ Simpler deployment
- ✅ Lower complexity
- ❌ Potential scaling challenges di masa depan
- ❌ Tightly coupled components

**Alternatives Considered**:
- Microservices: Rejected karena over-engineering untuk project ini
- Serverless: Rejected karena vendor lock-in dan complexity

---

### DEC-20260805-002: MySQL as Primary Database
**Date**: 2026-08-05  
**Category**: Database  
**Status**: Accepted  

**Context**:
Memilih database yang tepat untuk relational data dan kompleksitas query LaraNews.

**Decision**:
Menggunakan **MySQL 8.0+** sebagai primary database.

**Rationale**:
1. **Relational Model**: Data sangat relational (users, posts, comments, votes)
2. **ACID Compliance**: Data integrity penting untuk voting dan karma
3. **Laravel Support**: Excellent support di Laravel/Eloquent
4. **Performance**: Good performance untuk read-heavy workload
5. **Maturity**: Battle-tested dan stable
6. **Community**: Large community dan resources
7. **Full-Text Search**: Built-in full-text search capability

**Consequences**:
- ✅ Strong data integrity
- ✅ Excellent Laravel integration
- ✅ Rich query capabilities
- ❌ Vertical scaling limitations
- ❌ Potentially slower than NoSQL untuk certain operations

**Alternatives Considered**:
- PostgreSQL: Hampir dipilih, rejected karena team lebih familiar dengan MySQL
- MongoDB: Rejected karena relational nature of data
- SQLite: Rejected karena not suitable untuk production

---

### DEC-20260805-003: Redis for Caching and Queue
**Date**: 2026-08-05  
**Category**: Performance  
**Status**: Accepted  

**Context**:
Memerlukan caching layer dan queue system untuk performance dan async processing.

**Decision**:
Menggunakan **Redis** untuk cache, session storage, dan queue driver.

**Rationale**:
1. **Speed**: In-memory storage, extremely fast
2. **Multiple Use Cases**: Cache, sessions, queues, pub/sub
3. **Laravel Support**: Native support di Laravel
4. **Data Structures**: Rich data structures support
5. **Persistence**: Optional persistence untuk durability
6. **Atomic Operations**: Support untuk counter operations (votes, views)

**Consequences**:
- ✅ Significant performance improvement
- ✅ Reduced database load
- ✅ Fast session management
- ✅ Reliable queue system
- ❌ Additional infrastructure component
- ❌ Memory consumption

**Alternatives Considered**:
- Memcached: Rejected karena limited feature set vs Redis
- Database-based cache: Rejected karena performance concerns
- File-based cache: Rejected karena not suitable untuk distributed systems

---

### DEC-20260805-004: Blade + Alpine.js + Tailwind CSS
**Date**: 2026-08-05  
**Category**: UI/UX  
**Status**: Accepted  

**Context**:
Memilih frontend technology stack yang balance antara productivity dan modern UX.

**Decision**:
Menggunakan **Blade Templates** + **Alpine.js** + **Tailwind CSS** untuk frontend.

**Rationale**:
1. **Blade**: Native Laravel templating, SSR benefits
2. **Alpine.js**: Lightweight reactivity tanpa complexity SPA
3. **Tailwind CSS**: Utility-first, rapid UI development
4. **TALL Stack**: Proven combination di Laravel ecosystem
5. **SEO Friendly**: Server-side rendering
6. **Progressive Enhancement**: Works without JavaScript
7. **Developer Experience**: Fast development cycle

**Consequences**:
- ✅ Fast development
- ✅ SEO friendly
- ✅ Lightweight bundle size
- ✅ Great DX with Tailwind
- ❌ Not true SPA experience
- ❌ Full page reloads for navigation

**Alternatives Considered**:
- Vue.js/React SPA: Rejected karena added complexity dan SEO challenges
- Inertia.js: Considered, deferred untuk future enhancement
- Livewire: Considered, rejected karena preference untuk traditional approach

---

### DEC-20260805-005: Laravel Sanctum for API Authentication
**Date**: 2026-08-05  
**Category**: Security / API  
**Status**: Accepted  

**Context**:
Memerlukan API authentication system untuk mobile apps dan third-party integrations.

**Decision**:
Menggunakan **Laravel Sanctum** untuk API token authentication.

**Rationale**:
1. **Laravel Native**: Built-in Laravel, zero configuration
2. **Simplicity**: Simpler than Passport untuk token-based auth
3. **SPA Support**: Built-in support untuk SPA authentication
4. **Mobile-Friendly**: Perfect untuk mobile apps
5. **Performance**: Lightweight, no OAuth complexity
6. **Multiple Guards**: Support web dan API authentication

**Consequences**:
- ✅ Simple implementation
- ✅ Good performance
- ✅ Sufficient untuk use case kita
- ❌ No OAuth2 features (not needed now)
- ❌ Limited third-party app support

**Alternatives Considered**:
- Laravel Passport: Rejected karena over-engineering, OAuth2 not needed
- JWT: Rejected karena security concerns dan stateless complications
- Custom token system: Rejected karena reinventing the wheel

---

### DEC-20260805-006: Soft Deletes for User Content
**Date**: 2026-08-05  
**Category**: Database  
**Status**: Accepted  

**Context**:
Menentukan strategi deletion untuk user-generated content (posts, comments, users).

**Decision**:
Menggunakan **Soft Deletes** untuk semua user-generated content.

**Rationale**:
1. **Data Recovery**: Dapat restore content yang dihapus accidentally
2. **Audit Trail**: Preserve history untuk moderation
3. **User Experience**: "Undelete" functionality
4. **Referential Integrity**: Maintain relationships setelah deletion
5. **Analytics**: Historical data tetap tersedia
6. **Compliance**: Meet data retention requirements

**Consequences**:
- ✅ Content recovery possible
- ✅ Better audit trail
- ✅ Maintained referential integrity
- ❌ Increased storage usage
- ❌ Queries harus aware of soft deletes

**Alternatives Considered**:
- Hard Delete: Rejected karena data loss risk
- Archive Table: Rejected karena added complexity
- Flagging: Similar to soft delete, rejected karena Laravel has built-in solution

---

### DEC-20260805-007: Strategic Denormalization for Counters
**Date**: 2026-08-05  
**Category**: Database / Performance  
**Status**: Accepted  

**Context**:
Vote counts, comment counts, dan similar metrics akan di-query frequently. Calculating on-the-fly akan expensive.

**Decision**:
Menyimpan **counter fields** di parent tables (vote_score, comment_count, etc.) dengan event-driven updates.

**Rationale**:
1. **Performance**: Avoid expensive COUNT queries
2. **Ranking**: Enable fast sorting by score
3. **User Experience**: Instant display of counts
4. **Scalability**: Reduce database load significantly
5. **Feed Generation**: Fast feed generation using scores

**Consequences**:
- ✅ Significant performance improvement
- ✅ Fast ranking queries
- ✅ Reduced database load
- ❌ Data consistency risk
- ❌ Update complexity
- ❌ Storage overhead

**Alternatives Considered**:
- Real-time calculation: Rejected karena performance issues
- Materialized views: Rejected karena MySQL limitations
- Cache-only: Rejected karena data loss risk

**Mitigation**:
- Event-driven updates untuk consistency
- Scheduled jobs untuk verification dan correction
- Database transactions untuk atomic updates

---

### DEC-20260805-008: Repository Pattern
**Date**: 2026-08-05  
**Category**: Architecture  
**Status**: Accepted  

**Context**:
Menentukan data access pattern dan abstraction layer.

**Decision**:
Implementasi **Repository Pattern** dengan interface dan Eloquent implementation.

**Rationale**:
1. **Abstraction**: Decouple business logic dari data access
2. **Testability**: Easier to mock untuk unit tests
3. **Flexibility**: Dapat switch implementation tanpa change business logic
4. **Organization**: Clear separation of concerns
5. **Reusability**: Reusable query logic

**Consequences**:
- ✅ Better testability
- ✅ Clean architecture
- ✅ Flexible implementation
- ❌ Additional abstraction layer
- ❌ More files to maintain
- ❌ Potentially over-engineering untuk simple CRUD

**Alternatives Considered**:
- Direct Eloquent usage: Rejected karena tight coupling
- Query Builder only: Rejected karena lack of abstraction
- Active Record pattern only: Current Laravel default, enhanced dengan Repository

---

### DEC-20260805-009: URL-Based API Versioning
**Date**: 2026-08-05  
**Category**: API  
**Status**: Accepted  

**Context**:
Menentukan API versioning strategy untuk backward compatibility.

**Decision**:
Menggunakan **URL-based versioning** (`/api/v1/`, `/api/v2/`).

**Rationale**:
1. **Clarity**: Version jelas dari URL
2. **Simplicity**: Easy to understand dan implement
3. **Browser-Friendly**: Can test in browser easily
4. **Caching**: Different versions dapat di-cache independently
5. **Documentation**: Clear documentation per version
6. **Industry Standard**: Widely adopted practice

**Consequences**:
- ✅ Clear versioning
- ✅ Easy to implement
- ✅ Good DX
- ❌ URL changes dengan new version
- ❌ Multiple codebases untuk different versions

**Alternatives Considered**:
- Header-based versioning: Rejected karena less visible
- Query parameter versioning: Rejected karena not RESTful
- No versioning: Rejected karena breaking changes risk

---

### DEC-20260805-010: Hot Score Algorithm (Reddit-style)
**Date**: 2026-08-05  
**Category**: Performance / Algorithm  
**Status**: Accepted  

**Context**:
Menentukan ranking algorithm untuk "hot" feed yang balance antara votes dan time.

**Decision**:
Implementasi **time-decay voting algorithm** similar to Reddit's hot score.

**Rationale**:
1. **Proven**: Reddit algorithm sudah battle-tested
2. **Balance**: Balance antara popularity dan freshness
3. **Performance**: Can be pre-calculated dan stored
4. **User Experience**: Fresh content gets visibility
5. **Fair**: Older quality content dapat compete

**Consequences**:
- ✅ Balanced content discovery
- ✅ Fresh content visibility
- ✅ Quality content longevity
- ❌ Calculation overhead
- ❌ Periodic recalculation needed

**Alternatives Considered**:
- Simple vote count: Rejected karena bias to old content
- Time-only: Rejected karena ignores quality
- Hacker News algorithm: Considered, deferred untuk future A/B testing

**Implementation**:
```
hot_score = (upvotes - downvotes) / (age_in_hours + 2)^1.5
```

---

## Deferred Decisions

### DEF-20260805-001: Real-time Notifications
**Date**: 2026-08-05  
**Category**: Performance / Feature  
**Status**: Deferred  

**Context**:
Real-time notifications via WebSockets untuk better UX.

**Decision**:
Defer sampai Module 10 (Notification System). Start dengan polling, evaluate WebSocket need.

**Rationale**:
Fokus pada core features dulu, real-time dapat ditambahkan later tanpa breaking changes.

---

### DEF-20260805-002: Image Upload Service
**Date**: 2026-08-05  
**Category**: Infrastructure  
**Status**: Deferred  

**Context**:
External service (Cloudinary, S3) vs local storage untuk image uploads.

**Decision**:
Start dengan local storage, migrate ke cloud storage di Module 19 (Performance).

**Rationale**:
Simplify initial setup, cloud migration straightforward later.

---

## Superseded Decisions

_No superseded decisions yet._

---

## Rejected Decisions

### REJ-20260805-001: GraphQL API
**Date**: 2026-08-05  
**Category**: API  
**Status**: Rejected  

**Context**:
GraphQL vs REST untuk API.

**Decision**:
Rejected GraphQL, use REST API.

**Rationale**:
1. REST lebih familiar untuk most developers
2. GraphQL adds complexity
3. REST sufficient untuk use case kita
4. Better tooling support untuk REST
5. Easier to implement rate limiting dengan REST

---

## Template for New Decisions

```markdown
### DEC-YYYYMMDD-XXX: [Decision Title]
**Date**: YYYY-MM-DD  
**Category**: [Category]  
**Status**: [Status]  

**Context**:
[Describe the situation]

**Decision**:
[What was decided]

**Rationale**:
[Why this decision was made]

**Consequences**:
- ✅ [Positive consequence]
- ❌ [Negative consequence]

**Alternatives Considered**:
- [Alternative 1]: [Why rejected]
- [Alternative 2]: [Why rejected]
```

---

**Last Updated**: 2026-08-05  
**Document Owner**: Technical Lead  
**Review Cycle**: After each major decision
