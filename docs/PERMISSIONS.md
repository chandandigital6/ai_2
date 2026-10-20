# Access rules

| Identity | Access |
|---|---|
| Active super_admin | All platform/business operations, including across tenants. Inactive accounts are blocked. |
| Recorded business owner | Full access to their own active business. No global owner/admin Spatie role is required. |
| Manager/admin/staff membership | Only explicitly granted permissions on that business's active membership. A role label has no implicit permissions. |
| Personal/customer user | Own customer workflows and public marketplace; no seller module permissions from registration. |
| Inactive member/account | Seller access is revoked immediately, including when an API token still exists. |

## Assigning permissions

Open Business → Team & permissions. Enter an existing account email or fill name/mobile/password (12+ characters plus confirmation) to create a staff account. Select a role label and business permissions, then save. Select both view and manage/create/update permissions when a member needs the screen and its actions. Permission changes are read from the membership on each request, so token re-issuance is unnecessary.

Staff with staff.manage can administer delegated members, but may grant only permissions they themselves hold in that business. They cannot modify their own membership, the recorded owner, or a member holding permissions outside their authority. Removing a member removes access to that business; it does not delete their user account or memberships in other businesses. User credentials are never overwritten when an existing account is attached.

A staff.view grant permits listing and history only. Owner memberships cannot be edited/deleted from the staff page. Business creation and ownership transfer do not come from staff management permissions.

## Module catalog

The authoritative list is `config/business_permissions.php`. Products/services have view/create/update/delete. Orders, payments, returns, delivery, inventory, offers, gallery, reviews, enquiries, appointments, CRM, finance, coupons, promotions, documents and staff have separate permissions. customers.assign enables CRM manager assignment/report scope. chats.assign enables assignment/follow-up control. billing.view and subscriptions.view/request cover their independent workflows.

Public/customer routes retain their existing participant, ownership and visibility checks. Management routes additionally pass through AuthorizeBusinessModule. Adding a new business controller requires registering its read/write permissions in the config and seeding them; nested object ownership still must be checked in the controller/service.

Pending businesses can prepare profiles, staff, categories, attributes and KYC. Public showrooms and operational products/orders require approval. Existing quotas remain: the default no-subscription fallback allows one active non-owner staff member. A plan or admin quota override is required for more; this update does not silently bypass subscription limits.

Global platform categories/attributes use business_id NULL. Owner custom categories/attributes have the selected business_id. Owners cannot alter platform masters or another owner's entries. Product/service dropdowns include platform masters plus the current business entries. Attribute assignments reject another tenant's values. Deletion is refused while a category/value is in use.

Starter-kit personal Teams are a separate account feature. Their membership/global role changes do not become business membership or tenant permission grants.

## Release 2 modules

New tenant grants: ai.view/ai.use, commerce.view/commerce.manage, accounting.view/accounting.manage; existing social.view/social.manage, analytics.view, delivery.view/delivery.manage and payments.view/payments.manage now cover their new workflows. New platform grants: banners.manage, festivals.manage, reports.manage, audit.view, ai.credits.manage. Re-run BusinessPermissionSeeder; existing staff grants are not silently expanded.

New native controllers enforce BusinessAccessService directly. Workers recheck the requesting user's business grants before generation/publication. Customer/device/review/order actions retain their own ownership boundaries. The optional bearer guard on public read endpoints allows an authorized pending-photo reader without making unapproved photos public.
