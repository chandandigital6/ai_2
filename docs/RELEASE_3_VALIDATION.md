# Release 3 validation — 20 October 2026

- Full Laravel test suite: **220/220 passed**, 728 assertions, 46.15 seconds.
- 28 AI Studio feature cases, including provider error data sets, are included in the full count.
- Verified owner request / super-admin review, exactly-once approval credit, request idempotency, model override rejection, immutable package terms, expiration, quotas, private references/media, actual visual-input request bodies, text/image completion, once-only refund, stale job recovery and worker permission rechecks.
- Existing account, business authorization and other module feature tests passed.
- Both owner and admin Blade pages rendered in HTTP feature tests; all Blade templates compile with view:cache.
- PHP Pint applied to changed PHP source/test files. JavaScript syntax checked with node --check.
- Runtime API catalog matches updated Postman: **411 route templates**, including 20 AI endpoints (14 added in this release).
- Existing uploaded public media are included unchanged; runtime caches, vendor, database files and .env are excluded from the release ZIP.

## Scope of verification

Tests used local SQLite and fake HTTP/queue/storage providers. No real OpenAI key, paid generation, production MySQL concurrency stress test or live deployment was executed. Provider access, quotas, output quality, logo fidelity, and payment verification require staging acceptance with your own credentials. UI HTTP rendering and JS syntax were tested; interactive browser visual testing and Dart/Flutter compilation were not available in this environment. The Dart wrapper is integration source, not a compiled Flutter release.

Approval records a manual admin decision, not a gateway charge. Review payment externally before approving. Library quotas use the maximum allowance of active packs; per-generation cost/model/reference count always come from the selected immutable package.
