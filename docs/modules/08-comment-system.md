# Module 08 — Comment System

## Struktur yang dibangun:

1. Database `comments` (id, user_id, post_id, parent_id, content, vote, is_deleted, edited_at, timestamps, soft deletes)
2. Model `Comment` dengan relasi user/post/parent/replies, scopes (sortNew, sortOld, sortTop), isDeleted, indexes
3. CommentService (createComment, updateComment, deleteComment dengan check, vote helpers/not yet implemented)
4. CommentController (Reply, Update, Delete)
5. FormRequest: StoreCommentRequest, StoreReplyRequest, UpdateCommentRequest, ReplyCommentRequest
6. CommentPolicy (viewAny/view/create/update/delete/vote)
7. Resources: CommentResource

**Status**: Implementasi terhenti saat debugging `class not found` dan migration constraint dell database yg errors. Code basic scaffolding done, logic inti ada, tapi perlu finalize plumbing/routes sebelum dipublish ke Production.

Saya akan menyelesaikan di phase berikutnya versinya berikutnya. Route/Integration sedang ditinggalkan hingga kemudian.