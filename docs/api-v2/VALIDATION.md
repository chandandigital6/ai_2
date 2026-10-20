> Current release: see RELEASE_3_VALIDATION.md. This file retains the earlier Release 2 verification record.

# Release 2 validation

20 October 2026

- Full test suite: **192/192 passed**, 540 assertions, 42.3s. Includes 134 retained tests and 58 new module/security/integration regressions.
- PHP syntax: **1102 application/config/migration/test/compiled-view PHP files**, no parse errors.
- Blade compilation: passed. Seven business workspaces and five admin tool screens HTTP-rendered in tests; approved/pending review photos, banner visibility and the retained screens were exercised.
- Runtime API inventory, route-cache generation and route-cache clearing: passed.
- **397** runtime API v1 route templates match the structured reference and **397** Postman requests exactly; HTML contains 397 unique endpoint cards. Raw example JSON and embedded reference JavaScript syntax checked.
- Changed PHP files formatted with Laravel Pint. Frontend build assets from the previous release retained; new workspace styling is inline and existing assets are unchanged.

## New behavior exercised

Tenant denials; read/write grant independence; admin-only credit grants; wallet idempotency/overdraft/refund; provider-disabled failure; OTP persisted attempts/single use/2FA; device registration privacy/revocation scope; FCM v1 OAuth transport mock and invalid-token handling; push acknowledgement ownership; history/share availability; idempotent daily aggregation; review photo re-moderation/private bearer downloads; foreign social-account rejection; OAuth state validation; timezone recurrence; single publication and revoked-author checks; checkout quote/stale-total rejection and shipping snapshot; GST integer calculation; legacy delivered-action OTP/POD guards; OTP attempt persistence; forged Razorpay callback/webhook denial; captured exact-amount idempotency and interrupted-create reconciliation; balanced journals, foreign-account denial, request replay integrity and single reversal; public-setting secret-key rejection and active/expired banner display.

## Limits

PHP 8.3.6, SQLite in-memory and the included Composer lock were used. HTTP fakes exercised provider paths; no SMS, live push, OpenAI request, social publication or payment was sent. Actual service keys, app approval, Flutter SDK setup, MySQL locking under concurrent requests, and staging desktop/mobile/device acceptance remain outstanding. No screenshot browser QA or Dart SDK compilation is claimed.

Provider integrations default to disabled. Installation, supported core feature boundaries and manual provider-refund handling are documented in PENDING_MODULES_SETUP_HINDI.md and NEW_MODULES_API.md. No live deployment was performed.
