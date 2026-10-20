# Growya module status — release 2

20 October 2026. The former nine schema-only and three partial workstreams now have implementation paths. Provider-gated means code exists but actual credentials/permissions and live acceptance are still required; it does not mean a live integration was certified. The new modules are implemented to the supported core scope below.

| # | Workstream | State | Delivered scope | Evidence |
|---|---|---|---|---|
| 1 | Accounts, registration and Fortify login | Implemented | Web personal/business choice; Sanctum login/register/me/logout; authenticator check; account APIs. | `app/Actions/Fortify/CreateNewUser.php` |
| 2 | Mobile OTP login | Provider gated | Hashed single-use phone OTP, attempt/resend limits, active-account and confirmed-2FA checks; SMS adapter needs configured provider. | `app/Http/Controllers/Extensions/OtpController.php` |
| 3 | Country, state and city masters | Implemented | Seeders and location models; added paginated location API. | `database/seeders/IndiaLocationSeeder.php` |
| 4 | Business application and profiles | Implemented | Multiple businesses, owner records, pending/approved workflow, profile and locations. | `app/Http/Controllers/BusinessController.php` |
| 5 | Business staff and permissions | Implemented | Business-specific grants, create or attach staff, update/revoke/remove, permission delegation ceiling and audit history. | `app/Http/Controllers/BusinessStaffController.php` |
| 6 | Business hours and public social links | Implemented | Profile links and hours, independently permissioned; these are not OAuth-connected social accounts. | `app/Http/Controllers/BusinessHourController.php` |
| 7 | Business verification and KYC | Implemented | Verification and private document workflow uses growya_kyc_* tables; generic business_documents is not the operational storage. | `app/Http/Controllers/GrowyaBusinessKycController.php` |
| 8 | Categories | Implemented | Global master categories preserved; custom product/service categories scoped by business_id. | `app/Http/Controllers/TenantCatalogController.php` |
| 9 | Products, media, variants, tags, stock and QR | Implemented | Catalog CRUD, galleries, variants, CSV, stock movements, QR generation; QR failure preserves saved product/media. | `app/Http/Controllers/ProductController.php` |
| 10 | Product attributes and values | Implemented | Global defaults plus own attributes; product assignments reject another tenant values. | `app/Http/Controllers/ProductAttributeController.php` |
| 11 | Services and service images | Implemented | Services CRUD and image actions with business scoping. | `app/Http/Controllers/ServiceController.php` |
| 12 | Business gallery | Implemented | Gallery and media workflows with gallery.view/manage. | `app/Http/Controllers/BusinessGalleryController.php` |
| 13 | Marketplace, search, nearby and public showroom | Implemented | Existing discovery/showroom controllers have JSON counterparts. | `app/Http/Controllers/PublicBusinessController.php` |
| 14 | Offers and product/service links | Implemented | Existing offers CRUD and public offers. | `app/Http/Controllers/BusinessOfferController.php` |
| 15 | Business follows | Implemented | Follow/unfollow and own following list. | `app/Http/Controllers/BusinessFollowController.php` |
| 16 | Favourites and wishlist leads | Implemented | Business/product/service wishlist and owner interest workflows. | `app/Http/Controllers/GrowyaWishlistController.php` |
| 17 | Cart and checkout | Implemented | Per-user business carts, quantities, checkout and validation. | `app/Http/Controllers/GrowyaCartCheckoutController.php` |
| 18 | Orders and line snapshots | Implemented | Customer history and seller status workflow; order fields store snapshots. | `app/Services/GrowyaOrderService.php` |
| 19 | Product/service enquiries and follow-ups | Implemented | Existing enquiry, note and follow-up workflows with independent permission groups. | `app/Http/Controllers/ProductEnquiryController.php` |
| 20 | Ratings, review text and photo reviews | Implemented core | Review photo uploads, private access, max five photos, re-moderation and public approved-photo rendering. | `app/Http/Controllers/Extensions/ReviewPhotoController.php` |
| 21 | Saved customer addresses | Implemented | Existing saved address actions and default selection. | `app/Http/Controllers/GrowyaCustomerAddressController.php` |
| 22 | Appointments | Implemented | Appointment creation, listing and status flow. | `app/Http/Controllers/AppointmentController.php` |
| 23 | Notifications and delivery tracking | Provider gated | Per-device queued push outbox, provider acceptance, app delivery/open acknowledgements; in-app history preserved. | `app/Http/Controllers/Extensions/DeviceController.php` |
| 24 | FCM device registration and push sender | Provider gated | Device registration/rotation/revocation, FCM v1 service-account signing and invalid-token handling. | `app/Http/Controllers/Extensions/DeviceController.php` |
| 25 | AI requests and generated media | Provider gated | Queued text and single-image generation, protected media downloads, timeout/failure handling; no video generation. | `app/Http/Controllers/Extensions/AiController.php` |
| 26 | AI wallet and credit ledger | Implemented core | Locked balance/debit/refund/grant ledger, idempotent generation keys, failed-job refunds; admin-only grants. | `app/Http/Controllers/Extensions/AiController.php` |
| 27 | Connected social accounts and OAuth | Provider gated | Facebook authorization state, page discovery/selection, encrypted page tokens; reconnect on revoked access. | `app/Http/Controllers/Extensions/SocialController.php` |
| 28 | Social post creation and publishing | Provider gated | Draft/now/scheduled Facebook text/image and Instagram JPEG publishing with per-platform results and uncertain-state reconciliation. | `app/Http/Controllers/Extensions/SocialController.php` |
| 29 | Recurring social schedules and festivals | Provider gated | Daily/weekly timezone recurrence, missed-run coalescing, festival calendar administration and caption selection. | `app/Http/Controllers/Extensions/SocialController.php` |
| 30 | Analytics and daily summaries | Implemented core | Captured events plus idempotent hourly daily summaries for orders/follows/enquiries/notifications and scoped reads. | `app/Http/Controllers/Extensions/ActivityController.php` |
| 31 | Event stream, search logs, share links and recently viewed | Implemented core | Validated event capture, discovery/search logs, private history, expiring share codes and public availability rechecks. | `app/Http/Controllers/Extensions/ActivityController.php` |
| 32 | Plans, features and quotas | Implemented | Plan administration, feature entitlements, usage and business overrides. | `app/Services/GrowyaPlanLimitService.php` |
| 33 | Subscriptions and subscription billing documents | Implemented | Requests, admin confirmation, payment records and billing docs; does not settle money through a gateway. | `app/Http/Controllers/GrowyaSubscriptionController.php` |
| 34 | Customer CRM and staff performance | Implemented | Contacts, activity, assignments, kanban, targets, incentives and advanced views use growya_crm_*; generic business_customers/customer_notes are not active storage. | `app/Http/Controllers/GrowyaBusinessCrmController.php` |
| 35 | Generic reports, settings, banners and audit administration | Implemented core | Restricted presentation settings, banners with public display, festival tools, report moderation and immutable admin action history. | `app/Http/Controllers/Extensions/AdminToolsController.php` |
| 36 | Customer/business chat | Implemented | Threads, messages, uploads, assignment, blocking, reports and follow-ups; assigned conversation boundary retained. | `app/Http/Controllers/GrowyaChatController.php` |
| 37 | Support tickets | Implemented | Tickets, replies, internal support workflow and attachments. | `app/Http/Controllers/GrowyaSupportTicketController.php` |
| 38 | Referrals, rewards, coupons and paid promotions | Implemented | Existing manual approval and campaign workflows retained. | `app/Http/Controllers/GrowyaReferralController.php` |
| 39 | Order payment proofs, returns, refunds and delivery tracking | Implemented | Existing manual proofs/review, refund records and shipment events. Release 2 adds gated Razorpay settlement verification and delivery OTP/POD; manual return/refund records are retained. | `app/Http/Controllers/GrowyaPaymentController.php` |
| 40 | Basic finance ledger and reminders | Implemented | Existing income/expense reports, reminders and preferences. Release 2 adds a separate manual balanced journal/cash movement workflow; it does not automatically post this basic ledger. | `app/Http/Controllers/GrowyaFinanceController.php` |

## Additional previously deferred work

| Workstream | State | Supported scope |
|---|---|---|
| Business advanced permissions | Existing implementation retained and extended | New modules use separate tenant grants and server rechecks; global manager labels do not grant access. |
| Shipping/GST | Implemented core | Flat shipping, free threshold, one configured exclusive rate, CGST/SGST/IGST snapshot, checkout quote and stale-total rejection. |
| Razorpay | Provider gated | Provider orders, callback/webhook signatures, exact captured-payment verification, duplicate handling and interrupted-create reconciliation; refunds require operator action. |
| Delivery OTP/POD | Provider gated for SMS | Attempt-limited OTP, private photo/recipient evidence, confirmation and guards on legacy delivered paths. |
| Accounting/cash flow | Implemented core | Scoped chart, immutable balanced journals, reversal, trial balance, income/expense/profit and cash movement. Manual journals remain separate from the old finance ledger. |

## Remaining acceptance and extensions

Actual SMS mapping/DLT templates, Firebase/mobile configuration, Meta app approval, provider keys and live MySQL/concurrency/device acceptance are not completed by this source update. Additional product scope outside this core release includes AI video, social reels/carousels/Google Business/YouTube/X, automatic gateway refunds, multi-rate/HSN/inclusive-tax/statutory filing, automated accounting posting and bank reconciliation. Personal Teams/appearance/2FA setup remain starter-kit account UI, not business permission modules. See PENDING_MODULES_SETUP_HINDI.md and NEW_MODULES_API.md.
