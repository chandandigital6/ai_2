<?php


use App\Http\Controllers\Api\V1\AdminBusinessReviewController;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AttributeController;
use App\Http\Controllers\Api\V1\BusinessClaimController;
use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\V1\BusinessCrmController;
use App\Http\Controllers\Api\V1\BusinessFollowController;
use App\Http\Controllers\Api\V1\BusinessGalleryController;
use App\Http\Controllers\Api\V1\BusinessHourController;
use App\Http\Controllers\Api\V1\BusinessKycController;
use App\Http\Controllers\Api\V1\BusinessOfferController;
use App\Http\Controllers\Api\V1\BusinessOrderController;
use App\Http\Controllers\Api\V1\BusinessOwnershipController;
use App\Http\Controllers\Api\V1\BusinessPlanOverrideController;
use App\Http\Controllers\Api\V1\BusinessRequestController;
use App\Http\Controllers\Api\V1\BusinessReviewController;
use App\Http\Controllers\Api\V1\BusinessSocialLinkController;
use App\Http\Controllers\Api\V1\BusinessStaffController;
use App\Http\Controllers\Api\V1\BusinessVerificationController;
use App\Http\Controllers\Api\V1\CartCheckoutController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\ChatEnhancementController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\CrmAdvancedController;
use App\Http\Controllers\Api\V1\CrmIncentiveController;
use App\Http\Controllers\Api\V1\CrmKanbanController;
use App\Http\Controllers\Api\V1\CrmStaffController;
use App\Http\Controllers\Api\V1\CustomerAddressController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DeliveryTrackingController;
use App\Http\Controllers\Api\V1\Extensions\AccountingController;
use App\Http\Controllers\Api\V1\Extensions\ActivityController;
use App\Http\Controllers\Api\V1\Extensions\AdminToolsController;
use App\Http\Controllers\Api\V1\Extensions\AiBrandController;
use App\Http\Controllers\Api\V1\Extensions\AiController;
use App\Http\Controllers\Api\V1\Extensions\AiPlanController;
use App\Http\Controllers\Api\V1\Extensions\CommerceController;
use App\Http\Controllers\Api\V1\Extensions\DeliveryProofController;
use App\Http\Controllers\Api\V1\Extensions\DeviceController;
use App\Http\Controllers\Api\V1\Extensions\GatewayController;
use App\Http\Controllers\Api\V1\Extensions\OtpController;
use App\Http\Controllers\Api\V1\Extensions\QuoteController;
use App\Http\Controllers\Api\V1\Extensions\ReviewPhotoController;
use App\Http\Controllers\Api\V1\Extensions\SocialController;
use App\Http\Controllers\Api\V1\FinanceController;
use App\Http\Controllers\Api\V1\LeadInsightsController;
use App\Http\Controllers\Api\V1\LiveNotificationController;
use App\Http\Controllers\Api\V1\NearbyController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderReturnsController;
use App\Http\Controllers\Api\V1\PaidPromotionController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\PlanEntitlementController;
use App\Http\Controllers\Api\V1\ProductAttributeController;
use App\Http\Controllers\Api\V1\ProductBulkController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductEnquiryController;
use App\Http\Controllers\Api\V1\ProductImageController;
use App\Http\Controllers\Api\V1\ProductInventoryController;
use App\Http\Controllers\Api\V1\ProductQrCodeController;
use App\Http\Controllers\Api\V1\ProductTagController;
use App\Http\Controllers\Api\V1\ProductVariantAttributeController;
use App\Http\Controllers\Api\V1\ProductVariantController;
use App\Http\Controllers\Api\V1\PublicBusinessController;
use App\Http\Controllers\Api\V1\PublicGlobalSearchController;
use App\Http\Controllers\Api\V1\PublicHomeController;
use App\Http\Controllers\Api\V1\PublicMarketplaceController;
use App\Http\Controllers\Api\V1\PublicOfferController;
use App\Http\Controllers\Api\V1\PublicOffersExploreController;
use App\Http\Controllers\Api\V1\PublicProductController;
use App\Http\Controllers\Api\V1\PublicServiceEnquiryController;
use App\Http\Controllers\Api\V1\ReferralController;
use App\Http\Controllers\Api\V1\ReminderPreferenceController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\RoleMatrixController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\ServiceEnquiryController;
use App\Http\Controllers\Api\V1\ServiceEnquiryFollowUpController;
use App\Http\Controllers\Api\V1\ServiceImageController;
use App\Http\Controllers\Api\V1\SubscriptionBillingController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\SupportTicketController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\TenantCatalogController;
use App\Http\Controllers\Api\V1\UserRoleController;
use App\Http\Controllers\Api\V1\WishlistController;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\GrowyaSuperAdminOnly;
use App\Http\Middleware\OptionalGrowyaToken;
use App\Http\Middleware\RequireGrowyaToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Middleware presets
|--------------------------------------------------------------------------
| $public : open endpoints
| $auth   : logged-in user with Growya token
| $admin  : $auth + super admin only
| $ext(n) : extension routes, throttle n requests per minute
*/
$public = ['throttle:120,1'];
$auth = ['throttle:120,1', 'auth:sanctum', RequireGrowyaToken::class];
$admin = [...$auth, GrowyaSuperAdminOnly::class];
$ext = fn (int $perMinute): array => [
    "throttle:{$perMinute},1",
    'auth:sanctum',
    EnsureActiveAccount::class,
    RequireGrowyaToken::class,
];

Route::get('nearby-businesses', [NearbyController::class, 'index'])
    ->middleware($public)
    ->name('api.v1.growya.nearby.index');

Route::get('my-referrals', [ReferralController::class, 'mine'])
    ->middleware($auth)
    ->name('api.v1.growya.referrals.mine');

Route::get('offers', [PublicOffersExploreController::class, 'index'])
    ->middleware($public)
    ->name('api.v1.public.offers.index');

Route::get('my-notifications', [NotificationController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.growya.notifications.index');

Route::get('my-reminder-preferences', [ReminderPreferenceController::class, 'edit'])
    ->middleware($auth)
    ->name('api.v1.growya.reminders.preferences');

Route::patch('my-reminder-preferences', [ReminderPreferenceController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.growya.reminders.preferences.update');

Route::get('my-wishlist', [WishlistController::class, 'mine'])
    ->middleware($auth)
    ->name('api.v1.growya.wishlist.mine');

Route::get('my-messages', [ChatController::class, 'mine'])
    ->middleware($auth)
    ->name('api.v1.growya.chat.mine');

Route::get('my-business-claims', [BusinessClaimController::class, 'mine'])
    ->middleware($auth)
    ->name('api.v1.growya.claims.mine');

Route::get('my-following', [BusinessFollowController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.public.business.following');

Route::get('', [PublicHomeController::class, 'index'])
    ->middleware($public)
    ->name('api.v1.home');

Route::get('marketplace', [PublicMarketplaceController::class, 'index'])
    ->middleware($public)
    ->name('api.v1.public.marketplace.index');

Route::get('search', [PublicGlobalSearchController::class, 'index'])
    ->middleware($public)
    ->name('api.v1.public.search.index');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.dashboard');

Route::get('business-applications', [BusinessRequestController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.growya.business-request.status');

Route::get('permissions', [PermissionController::class, 'index'])
    ->middleware($admin)
    ->name('api.v1.permissions.index');

Route::get('my-orders', [CartCheckoutController::class, 'myOrders'])
    ->middleware($auth)
    ->name('api.v1.growya.orders.mine');

Route::get('my-addresses', [CustomerAddressController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.growya.addresses.index');

Route::post('my-addresses', [CustomerAddressController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.growya.addresses.store');

Route::get('businesses', [BusinessController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.businesses.index');

Route::post('businesses', [BusinessController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.businesses.store');

Route::get('categories', [CategoryController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.categories.index');

Route::post('categories', [CategoryController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.categories.store');

Route::get('tags', [TagController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.tags.index');

Route::post('tags', [TagController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.tags.store');

Route::get('attributes', [AttributeController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.attributes.index');

Route::post('attributes', [AttributeController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.attributes.store');

Route::get('roles', [RoleController::class, 'index'])
    ->middleware($admin)
    ->name('api.v1.action.27');

Route::post('roles', [RoleController::class, 'store'])
    ->middleware($admin)
    ->name('api.v1.action.28');

Route::get('admin/referrals', [ReferralController::class, 'admin'])
    ->middleware($admin)
    ->name('api.v1.growya.referrals.admin');

Route::get('my-notifications/poll', [LiveNotificationController::class, 'poll'])
    ->middleware([...$auth, 'throttle:90,1'])
    ->name('api.v1.growya.notifications.poll');

Route::patch('my-notifications/read-all', [NotificationController::class, 'readAll'])
    ->middleware($auth)
    ->name('api.v1.growya.notifications.read-all');

Route::get('admin/lead-insights', [LeadInsightsController::class, 'admin'])
    ->middleware($admin)
    ->name('api.v1.growya.lead-insights.admin');

Route::get('admin/business-reviews', [AdminBusinessReviewController::class, 'index'])
    ->middleware($admin)
    ->name('api.v1.admin.business-reviews.index');

Route::get('admin/wishlist-interests', [WishlistController::class, 'admin'])
    ->middleware($admin)
    ->name('api.v1.growya.admin.wishlist.index');

Route::get('admin/subscription-plans', [PlanController::class, 'index'])
    ->middleware($admin)
    ->name('api.v1.growya.plans.index');

Route::post('admin/subscription-plans', [PlanController::class, 'store'])
    ->middleware($admin)
    ->name('api.v1.growya.plans.store');

Route::get('admin/subscriptions', [SubscriptionController::class, 'adminIndex'])
    ->middleware($admin)
    ->name('api.v1.growya.subscriptions.admin');

Route::get('admin/paid-promotion-packages', [PaidPromotionController::class, 'packages'])
    ->middleware($admin)
    ->name('api.v1.growya.promotions.packages');

Route::post('admin/paid-promotion-packages', [PaidPromotionController::class, 'storePackage'])
    ->middleware($admin)
    ->name('api.v1.growya.promotions.packages.store');

Route::get('admin/paid-promotions', [PaidPromotionController::class, 'adminIndex'])
    ->middleware($admin)
    ->name('api.v1.growya.promotions.admin');

Route::get('admin/analytics', [AnalyticsController::class, 'admin'])
    ->middleware($admin)
    ->name('api.v1.growya.analytics.admin');

Route::get('support/tickets', [SupportTicketController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.growya.support.index');

Route::post('support/tickets', [SupportTicketController::class, 'store'])
    ->middleware([...$auth, 'throttle:5,1'])
    ->name('api.v1.growya.support.store');

Route::get('admin/business-kyc', [BusinessKycController::class, 'admin'])
    ->middleware($admin)
    ->name('api.v1.growya.kyc.admin');

Route::get('admin/chat-reports', [ChatEnhancementController::class, 'reports'])
    ->middleware($admin)
    ->name('api.v1.growya.chat.reports');

Route::get('my-messages/unread-count', [ChatEnhancementController::class, 'unread'])
    ->middleware([...$auth, 'throttle:90,1'])
    ->name('api.v1.growya.chat.unread');

Route::get('admin/business-claims', [BusinessClaimController::class, 'admin'])
    ->middleware($admin)
    ->name('api.v1.growya.claims.admin');

Route::get('admin/subscription-billing', [SubscriptionBillingController::class, 'adminIndex'])
    ->middleware($admin)
    ->name('api.v1.growya.billing.admin');

Route::get('search/suggestions', [PublicGlobalSearchController::class, 'suggest'])
    ->middleware([...$public, 'throttle:30,1'])
    ->name('api.v1.public.search.suggest');

Route::post('permissions/assign', [PermissionController::class, 'assign'])
    ->middleware($admin)
    ->name('api.v1.permissions.assign');

Route::post('permissions/store', [PermissionController::class, 'store'])
    ->middleware($admin)
    ->name('api.v1.permissions.store');

Route::get('admin/access-control', [RoleMatrixController::class, 'index'])
    ->middleware($admin)
    ->name('api.v1.growya.roles.matrix');

Route::get('admin/user-roles', [UserRoleController::class, 'index'])
    ->middleware($admin)
    ->name('api.v1.growya.users.roles.index');

Route::get('admin/business-ownership', [BusinessOwnershipController::class, 'index'])
    ->middleware($admin)
    ->name('api.v1.growya.ownership.index');

Route::get('businesses/create', [BusinessController::class, 'create'])
    ->middleware($auth)
    ->name('api.v1.businesses.create');

Route::get('categories/create', [CategoryController::class, 'create'])
    ->middleware($auth)
    ->name('api.v1.categories.create');

Route::get('admin/business-verifications', [BusinessVerificationController::class, 'index'])
    ->middleware($admin)
    ->name('api.v1.admin.business-verifications.index');

Route::get('roles/create', [RoleController::class, 'create'])
    ->middleware($admin)
    ->name('api.v1.action.58');

Route::get('promoted/{promotion}', [PaidPromotionController::class, 'click'])
    ->middleware([...$public, 'throttle:60,1'])
    ->name('api.v1.growya.promotions.click');

Route::get('messages/{thread}', [ChatController::class, 'show'])
    ->middleware($auth)
    ->name('api.v1.growya.chat.show');

Route::post('messages/{thread}', [ChatController::class, 'send'])
    ->middleware([...$auth, 'throttle:60,1'])
    ->name('api.v1.growya.chat.send');

Route::get('business-claims/{claim}', [BusinessClaimController::class, 'show'])
    ->middleware($auth)
    ->name('api.v1.growya.claims.show');

Route::get('subscription-billing/{document}', [SubscriptionBillingController::class, 'show'])
    ->middleware($auth)
    ->name('api.v1.growya.billing.show');

Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])
    ->middleware($admin)
    ->name('api.v1.permissions.destroy');

Route::get('my-orders/{order}', [CartCheckoutController::class, 'order'])
    ->middleware($auth)
    ->name('api.v1.growya.orders.show');

Route::put('my-addresses/{address}', [CustomerAddressController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.growya.addresses.update');

Route::delete('my-addresses/{address}', [CustomerAddressController::class, 'destroy'])
    ->middleware($auth)
    ->name('api.v1.growya.addresses.destroy');

Route::get('businesses/{business}', [BusinessController::class, 'show'])
    ->middleware($auth)
    ->name('api.v1.businesses.show');

Route::put('businesses/{business}', [BusinessController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.businesses.update');

Route::delete('businesses/{business}', [BusinessController::class, 'destroy'])
    ->middleware($auth)
    ->name('api.v1.businesses.destroy');

Route::put('categories/{category}', [CategoryController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.categories.update');

Route::delete('categories/{category}', [CategoryController::class, 'destroy'])
    ->middleware($auth)
    ->name('api.v1.categories.destroy');

Route::put('tags/{tag}', [TagController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.tags.update');

Route::delete('tags/{tag}', [TagController::class, 'destroy'])
    ->middleware($auth)
    ->name('api.v1.tags.destroy');

Route::put('attributes/{attribute}', [AttributeController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.attributes.update');

Route::delete('attributes/{attribute}', [AttributeController::class, 'destroy'])
    ->middleware($auth)
    ->name('api.v1.attributes.destroy');

Route::get('b/{slug}', [PublicBusinessController::class, 'home'])
    ->middleware($public)
    ->name('api.v1.public.business.home');

Route::get('roles/{role}', [RoleController::class, 'show'])
    ->middleware($admin)
    ->name('api.v1.action.78');

Route::put('roles/{role}', [RoleController::class, 'update'])
    ->middleware($admin)
    ->name('api.v1.action.79');

Route::delete('roles/{role}', [RoleController::class, 'destroy'])
    ->middleware($admin)
    ->name('api.v1.action.80');

Route::patch('admin/referrals/settings', [ReferralController::class, 'settings'])
    ->middleware($admin)
    ->name('api.v1.growya.referrals.settings');

Route::get('admin/lead-insights/export', [LeadInsightsController::class, 'adminExport'])
    ->middleware($admin)
    ->name('api.v1.growya.lead-insights.admin.export');

Route::get('admin/analytics/export', [AnalyticsController::class, 'adminExport'])
    ->middleware($admin)
    ->name('api.v1.growya.analytics.admin.export');

Route::get('support/tickets/create', [SupportTicketController::class, 'create'])
    ->middleware($auth)
    ->name('api.v1.growya.support.create');

Route::get('admin/subscription-billing/export', [SubscriptionBillingController::class, 'export'])
    ->middleware($admin)
    ->name('api.v1.growya.billing.export');

Route::get('b/{business:slug}/offers', [PublicOfferController::class, 'index'])
    ->middleware($public)
    ->name('api.v1.public.business.offers');

Route::post('my-notifications/{recipient}/open', [NotificationController::class, 'open'])
    ->middleware($auth)
    ->name('api.v1.growya.notifications.open');

Route::patch('my-notifications/{recipient}/read', [NotificationController::class, 'read'])
    ->middleware($auth)
    ->name('api.v1.growya.notifications.read');

Route::get('b/{slug}/reviews', [BusinessReviewController::class, 'publicIndex'])
    ->middleware($public)
    ->name('api.v1.public.business.reviews.index');

Route::get('businesses/{business}/finance', [FinanceController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.growya.finance.index');

Route::post('businesses/{business}/finance', [FinanceController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.growya.finance.store');

Route::get('businesses/{business}/crm-incentives', [CrmIncentiveController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.incentives.index');

Route::get('businesses/{business}/crm-staff', [CrmStaffController::class, 'dashboard'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.staff.dashboard');

Route::get('businesses/{business}/crm-kanban', [CrmKanbanController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.kanban.index');

Route::get('businesses/{business}/lead-insights', [LeadInsightsController::class, 'owner'])
    ->middleware($auth)
    ->name('api.v1.growya.lead-insights.owner');

Route::get('businesses/{business}/crm', [BusinessCrmController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.index');

Route::post('businesses/{business}/crm', [BusinessCrmController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.store');

Route::post('b/{slug}/reviews', [BusinessReviewController::class, 'store'])
    ->middleware([...$auth, 'throttle:5,1'])
    ->name('api.v1.public.business.reviews.store');

Route::get('businesses/{business}/reviews', [BusinessReviewController::class, 'ownerIndex'])
    ->middleware($auth)
    ->name('api.v1.businesses.reviews.index');

Route::get('businesses/{business}/map-location', [NearbyController::class, 'edit'])
    ->middleware($auth)
    ->name('api.v1.growya.nearby.location.edit');

Route::patch('businesses/{business}/map-location', [NearbyController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.growya.nearby.location.update');

Route::get('businesses/{business}/wishlist-interests', [WishlistController::class, 'seller'])
    ->middleware($auth)
    ->name('api.v1.businesses.wishlist.index');

Route::patch('admin/wishlist-interests/{interest}', [WishlistController::class, 'adminUpdate'])
    ->middleware($admin)
    ->name('api.v1.growya.admin.wishlist.update');

Route::get('businesses/{business}/coupons', [CouponController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.businesses.coupons.index');

Route::post('businesses/{business}/coupons', [CouponController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.businesses.coupons.store');

Route::patch('admin/subscription-plans/{plan}', [PlanController::class, 'update'])
    ->middleware($admin)
    ->name('api.v1.growya.plans.update');

Route::get('businesses/{business}/subscriptions', [SubscriptionController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.growya.subscriptions.business');

Route::post('businesses/{business}/subscriptions', [SubscriptionController::class, 'request'])
    ->middleware([...$auth, 'throttle:5,1'])
    ->name('api.v1.growya.subscriptions.request');

Route::get('businesses/{business}/plan-usage', [PlanEntitlementController::class, 'usage'])
    ->middleware($auth)
    ->name('api.v1.growya.entitlements.usage');

Route::patch('admin/paid-promotion-packages/{package}', [PaidPromotionController::class, 'updatePackage'])
    ->middleware($admin)
    ->name('api.v1.growya.promotions.packages.update');

Route::patch('admin/paid-promotions/{promotion}', [PaidPromotionController::class, 'review'])
    ->middleware($admin)
    ->name('api.v1.growya.promotions.review');

Route::get('businesses/{business}/paid-promotions', [PaidPromotionController::class, 'ownerIndex'])
    ->middleware($auth)
    ->name('api.v1.growya.promotions.owner');

Route::post('businesses/{business}/paid-promotions', [PaidPromotionController::class, 'request'])
    ->middleware([...$auth, 'throttle:5,1'])
    ->name('api.v1.growya.promotions.owner.request');

Route::get('businesses/{business}/analytics', [AnalyticsController::class, 'owner'])
    ->middleware($auth)
    ->name('api.v1.growya.analytics.owner');

Route::get('support/tickets/{ticket}', [SupportTicketController::class, 'show'])
    ->middleware($auth)
    ->name('api.v1.growya.support.show');

Route::get('businesses/{business}/kyc', [BusinessKycController::class, 'owner'])
    ->middleware($auth)
    ->name('api.v1.growya.kyc.owner');

Route::post('businesses/{business}/kyc', [BusinessKycController::class, 'submit'])
    ->middleware([...$auth, 'throttle:3,1'])
    ->name('api.v1.growya.kyc.submit');

Route::get('admin/business-kyc/{submission}', [BusinessKycController::class, 'adminShow'])
    ->middleware($admin)
    ->name('api.v1.growya.kyc.admin.show');

Route::patch('admin/business-kyc/{submission}', [BusinessKycController::class, 'review'])
    ->middleware($admin)
    ->name('api.v1.growya.kyc.review');

Route::get('businesses/{business}/messages', [ChatController::class, 'inbox'])
    ->middleware($auth)
    ->name('api.v1.growya.chat.inbox');

Route::get('messages/{thread}/poll', [ChatController::class, 'poll'])
    ->middleware($auth)
    ->name('api.v1.growya.chat.poll');

Route::patch('messages/{thread}/assign', [ChatEnhancementController::class, 'assign'])
    ->middleware($auth)
    ->name('api.v1.growya.chat.assign');

Route::patch('messages/{thread}/block', [ChatEnhancementController::class, 'block'])
    ->middleware($auth)
    ->name('api.v1.growya.chat.block');

Route::post('messages/{thread}/report', [ChatEnhancementController::class, 'report'])
    ->middleware([...$auth, 'throttle:5,1'])
    ->name('api.v1.growya.chat.report');

Route::patch('messages/{thread}/followup', [ChatEnhancementController::class, 'followup'])
    ->middleware($auth)
    ->name('api.v1.growya.chat.followup');

Route::get('messages/{thread}/activity', [ChatEnhancementController::class, 'activity'])
    ->middleware($auth)
    ->name('api.v1.growya.chat.activity');

Route::patch('admin/chat-reports/{report}', [ChatEnhancementController::class, 'review'])
    ->middleware($admin)
    ->name('api.v1.growya.chat.reports.review');

Route::get('businesses/{business}/claim', [BusinessClaimController::class, 'create'])
    ->middleware($auth)
    ->name('api.v1.growya.claims.create');

Route::post('businesses/{business}/claim', [BusinessClaimController::class, 'store'])
    ->middleware([...$auth, 'throttle:3,1'])
    ->name('api.v1.growya.claims.store');

Route::get('businesses/{business}/billing', [SubscriptionBillingController::class, 'ownerIndex'])
    ->middleware($auth)
    ->name('api.v1.growya.billing.owner');

Route::get('subscription-billing/{document}/pdf', [SubscriptionBillingController::class, 'pdf'])
    ->middleware($auth)
    ->name('api.v1.growya.billing.pdf');

Route::post('businesses/{business}/follow', [BusinessFollowController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.public.business.follow');

Route::delete('businesses/{business}/follow', [BusinessFollowController::class, 'destroy'])
    ->middleware($auth)
    ->name('api.v1.public.business.unfollow');

Route::patch('admin/user-roles/{user}', [UserRoleController::class, 'update'])
    ->middleware($admin)
    ->name('api.v1.growya.users.roles.update');

Route::patch('admin/business-ownership/{business}', [BusinessOwnershipController::class, 'update'])
    ->middleware([...$admin, 'throttle:10,1'])
    ->name('api.v1.growya.ownership.update');

Route::get('my-orders/{order}/payment', [PaymentController::class, 'customerIndex'])
    ->middleware($auth)
    ->name('api.v1.growya.payments.customer');

Route::post('my-orders/{order}/payment', [PaymentController::class, 'submit'])
    ->middleware([...$auth, 'throttle:5,1'])
    ->name('api.v1.growya.payments.submit');

Route::get('businesses/{business}/payments', [PaymentController::class, 'sellerIndex'])
    ->middleware($auth)
    ->name('api.v1.businesses.payments.index');

Route::get('my-orders/{order}/returns', [OrderReturnsController::class, 'customerIndex'])
    ->middleware($auth)
    ->name('api.v1.growya.returns.customer');

Route::post('my-orders/{order}/returns', [OrderReturnsController::class, 'submit'])
    ->middleware([...$auth, 'throttle:5,1'])
    ->name('api.v1.growya.returns.submit');

Route::get('businesses/{business}/returns', [OrderReturnsController::class, 'sellerIndex'])
    ->middleware($auth)
    ->name('api.v1.businesses.returns.index');

Route::get('my-orders/{order}/tracking', [DeliveryTrackingController::class, 'customer'])
    ->middleware($auth)
    ->name('api.v1.growya.orders.tracking');

Route::get('businesses/{business}/delivery', [DeliveryTrackingController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.businesses.delivery.index');

Route::get('shops/{business}/cart', [CartCheckoutController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.growya.cart.index');

Route::post('shops/{business}/cart', [CartCheckoutController::class, 'add'])
    ->middleware($auth)
    ->name('api.v1.growya.cart.add');

Route::get('shops/{business}/checkout', [CartCheckoutController::class, 'checkoutForm'])
    ->middleware($auth)
    ->name('api.v1.growya.checkout.form');

Route::post('shops/{business}/checkout', [CartCheckoutController::class, 'place'])
    ->middleware($auth)
    ->name('api.v1.growya.checkout.place');

Route::get('businesses/{business}/orders', [BusinessOrderController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.businesses.orders.index');

Route::patch('my-addresses/{address}/default', [CustomerAddressController::class, 'setDefault'])
    ->middleware($auth)
    ->name('api.v1.growya.addresses.default');

Route::get('businesses/{business}/product-enquiries', [ProductEnquiryController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.businesses.product-enquiries.index');

Route::get('businesses/{business}/edit', [BusinessController::class, 'edit'])
    ->middleware($auth)
    ->name('api.v1.businesses.edit');

Route::get('businesses/{business}/hours', [BusinessHourController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.businesses.hours.index');

Route::post('businesses/{business}/hours', [BusinessHourController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.businesses.hours.store');

Route::get('businesses/{business}/social-links', [BusinessSocialLinkController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.businesses.social-links.index');

Route::post('businesses/{business}/social-links', [BusinessSocialLinkController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.businesses.social-links.store');

Route::get('businesses/{business}/staff', [BusinessStaffController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.businesses.staff.index');

Route::post('businesses/{business}/staff', [BusinessStaffController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.businesses.staff.store');

Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])
    ->middleware($auth)
    ->name('api.v1.categories.edit');

Route::get('businesses/{business}/products', [ProductController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.index');

Route::post('businesses/{business}/products', [ProductController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.store');

Route::post('attributes/{attribute}/values', [AttributeController::class, 'storeValue'])
    ->middleware($auth)
    ->name('api.v1.attributes.values.store');

Route::get('businesses/{business}/services', [ServiceController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.businesses.services.index');

Route::post('businesses/{business}/services', [ServiceController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.businesses.services.store');

Route::get('businesses/{business}/service-enquiries', [ServiceEnquiryController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.businesses.service-enquiries.index');

Route::get('businesses/{business}/service-follow-ups', [ServiceEnquiryFollowUpController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.businesses.service-follow-ups.index');

Route::get('businesses/{business}/appointments', [AppointmentController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.businesses.appointments.index');

Route::post('businesses/{business}/appointments', [AppointmentController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.businesses.appointments.store');

Route::get('businesses/{business}/gallery', [BusinessGalleryController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.businesses.gallery.index');

Route::post('businesses/{business}/gallery', [BusinessGalleryController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.businesses.gallery.store');

Route::get('admin/business-verifications/{business}', [BusinessVerificationController::class, 'show'])
    ->middleware($admin)
    ->name('api.v1.admin.business-verifications.show');

Route::get('businesses/{business}/offers', [BusinessOfferController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.businesses.offers.index');

Route::post('businesses/{business}/offers', [BusinessOfferController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.businesses.offers.store');

Route::get('b/{slug}/products', [PublicBusinessController::class, 'products'])
    ->middleware($public)
    ->name('api.v1.public.business.products');

Route::get('b/{slug}/services', [PublicBusinessController::class, 'services'])
    ->middleware($public)
    ->name('api.v1.public.business.services');

Route::get('b/{slug}/gallery', [PublicBusinessController::class, 'gallery'])
    ->middleware($public)
    ->name('api.v1.public.business.gallery');

Route::get('b/{slug}/contact', [PublicBusinessController::class, 'contact'])
    ->middleware($public)
    ->name('api.v1.public.business.contact');

Route::get('roles/{role}/edit', [RoleController::class, 'edit'])
    ->middleware($admin)
    ->name('api.v1.action.177');

Route::post('my-wishlist/{type}/{id}', [WishlistController::class, 'add'])
    ->middleware([...$auth, 'throttle:15,1'])
    ->name('api.v1.growya.wishlist.add');

Route::delete('my-wishlist/{type}/{id}', [WishlistController::class, 'remove'])
    ->middleware($auth)
    ->name('api.v1.growya.wishlist.remove');

Route::get('p/{business}/{product}', [PublicProductController::class, 'show'])
    ->middleware($public)
    ->name('api.v1.public.products.show');

Route::post('admin/referrals/{referral}/approve', [ReferralController::class, 'approveBusinessReward'])
    ->middleware($admin)
    ->name('api.v1.growya.referrals.approve');

Route::post('businesses/{business}/finance/categories', [FinanceController::class, 'storeCategory'])
    ->middleware($auth)
    ->name('api.v1.growya.finance.categories.store');

Route::get('businesses/{business}/finance/export', [FinanceController::class, 'export'])
    ->middleware($auth)
    ->name('api.v1.growya.finance.export');

Route::get('businesses/{business}/crm-incentives/report', [CrmIncentiveController::class, 'report'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.incentives.report');

Route::get('businesses/{business}/crm-incentives/export', [CrmIncentiveController::class, 'export'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.incentives.export');

Route::post('businesses/{business}/crm-incentives/rules', [CrmIncentiveController::class, 'rule'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.incentives.rule');

Route::post('businesses/{business}/crm-incentives/claims', [CrmIncentiveController::class, 'claim'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.incentives.claim');

Route::get('businesses/{business}/crm-staff/report', [CrmStaffController::class, 'report'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.staff.report');

Route::post('businesses/{business}/crm-staff/targets', [CrmStaffController::class, 'target'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.staff.target');

Route::get('businesses/{business}/lead-insights/export', [LeadInsightsController::class, 'ownerExport'])
    ->middleware($auth)
    ->name('api.v1.growya.lead-insights.owner.export');

Route::get('businesses/{business}/crm/create', [BusinessCrmController::class, 'create'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.create');

Route::get('businesses/{business}/crm/export', [BusinessCrmController::class, 'export'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.export');

Route::post('businesses/{business}/crm/import', [BusinessCrmController::class, 'import'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.import');

Route::patch('admin/business-reviews/{review}/moderate', [AdminBusinessReviewController::class, 'update'])
    ->middleware($admin)
    ->name('api.v1.admin.business-reviews.update');

Route::get('businesses/{business}/coupons/create', [CouponController::class, 'create'])
    ->middleware($auth)
    ->name('api.v1.businesses.coupons.create');

Route::patch('admin/subscriptions/{subscription}/review', [SubscriptionController::class, 'review'])
    ->middleware($admin)
    ->name('api.v1.growya.subscriptions.review');

Route::get('admin/subscription-plans/{plan}/limits', [PlanEntitlementController::class, 'edit'])
    ->middleware($admin)
    ->name('api.v1.growya.entitlements.edit');

Route::patch('admin/subscription-plans/{plan}/limits', [PlanEntitlementController::class, 'update'])
    ->middleware($admin)
    ->name('api.v1.growya.entitlements.update');

Route::get('admin/businesses/{business}/plan-overrides', [BusinessPlanOverrideController::class, 'edit'])
    ->middleware($admin)
    ->name('api.v1.growya.plan-overrides.edit');

Route::post('admin/businesses/{business}/plan-overrides', [BusinessPlanOverrideController::class, 'save'])
    ->middleware($admin)
    ->name('api.v1.growya.plan-overrides.save');

Route::get('businesses/{business}/analytics/export', [AnalyticsController::class, 'ownerExport'])
    ->middleware($auth)
    ->name('api.v1.growya.analytics.owner.export');

Route::post('support/tickets/{ticket}/reply', [SupportTicketController::class, 'reply'])
    ->middleware([...$auth, 'throttle:15,1'])
    ->name('api.v1.growya.support.reply');

Route::patch('support/tickets/{ticket}/status', [SupportTicketController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.growya.support.update');

Route::patch('support/tickets/{ticket}/assign', [SupportTicketController::class, 'assign'])
    ->middleware($auth)
    ->name('api.v1.growya.support.assign');

Route::post('businesses/{business}/messages/start', [ChatController::class, 'start'])
    ->middleware([...$auth, 'throttle:15,1'])
    ->name('api.v1.growya.chat.start');

Route::patch('admin/business-claims/{claim}/review', [BusinessClaimController::class, 'review'])
    ->middleware($admin)
    ->name('api.v1.growya.claims.review');

Route::post('admin/subscription-billing/payments/{payment}', [SubscriptionBillingController::class, 'issue'])
    ->middleware([...$admin, 'throttle:10,1'])
    ->name('api.v1.growya.billing.issue');

Route::patch('admin/access-control/roles/{role}', [RoleMatrixController::class, 'update'])
    ->middleware($admin)
    ->name('api.v1.growya.roles.matrix.update');

Route::get('admin/business-ownership/{business}/edit', [BusinessOwnershipController::class, 'edit'])
    ->middleware($admin)
    ->name('api.v1.growya.ownership.edit');

Route::get('businesses/{business}/staff/history', [BusinessStaffController::class, 'history'])
    ->middleware($auth)
    ->name('api.v1.businesses.staff.history');

Route::get('businesses/{business}/products/bulk', [ProductBulkController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.bulk.index');

Route::get('businesses/{business}/products/create', [ProductController::class, 'create'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.create');

Route::get('businesses/{business}/services/create', [ServiceController::class, 'create'])
    ->middleware($auth)
    ->name('api.v1.businesses.services.create');

Route::get('businesses/{business}/appointments/create', [AppointmentController::class, 'create'])
    ->middleware($auth)
    ->name('api.v1.businesses.appointments.create');

Route::get('businesses/{business}/gallery/create', [BusinessGalleryController::class, 'create'])
    ->middleware($auth)
    ->name('api.v1.businesses.gallery.create');

Route::patch('admin/business-verifications/{business}/approve', [BusinessVerificationController::class, 'approve'])
    ->middleware($admin)
    ->name('api.v1.admin.business-verifications.approve');

Route::patch('admin/business-verifications/{business}/reject', [BusinessVerificationController::class, 'reject'])
    ->middleware($admin)
    ->name('api.v1.admin.business-verifications.reject');

Route::patch('admin/business-verifications/{business}/pending', [BusinessVerificationController::class, 'pending'])
    ->middleware($admin)
    ->name('api.v1.admin.business-verifications.pending');

Route::get('businesses/{business}/offers/create', [BusinessOfferController::class, 'create'])
    ->middleware($auth)
    ->name('api.v1.businesses.offers.create');

Route::get('products/{business}/{product}/enquiry', [ProductEnquiryController::class, 'create'])
    ->middleware($auth)
    ->name('api.v1.public.products.enquiry.create');

Route::post('products/{business}/{product}/enquiry', [ProductEnquiryController::class, 'store'])
    ->middleware([...$auth, 'throttle:5,1'])
    ->name('api.v1.public.products.enquiry.store');

Route::patch('businesses/{business}/finance/{entry}', [FinanceController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.growya.finance.update');

Route::delete('businesses/{business}/finance/{entry}', [FinanceController::class, 'destroy'])
    ->middleware($auth)
    ->name('api.v1.growya.finance.destroy');

Route::get('businesses/{business}/crm-staff/{contact}', [CrmStaffController::class, 'show'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.staff.show');

Route::patch('businesses/{business}/crm-kanban/{contact}', [CrmKanbanController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.kanban.update');

Route::get('businesses/{business}/crm/{contact}', [BusinessCrmController::class, 'show'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.show');

Route::patch('businesses/{business}/crm/{contact}', [BusinessCrmController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.update');

Route::put('b/{slug}/reviews/{review}', [BusinessReviewController::class, 'update'])
    ->middleware([...$auth, 'throttle:5,1'])
    ->name('api.v1.public.business.reviews.update');

Route::patch('businesses/{business}/wishlist-interests/{interest}', [WishlistController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.businesses.wishlist.update');

Route::patch('businesses/{business}/coupons/{coupon}', [CouponController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.businesses.coupons.update');

Route::get('business-kyc/{submission}/documents/{document}', [BusinessKycController::class, 'download'])
    ->middleware($auth)
    ->name('api.v1.growya.kyc.download');

Route::get('messages/{thread}/files/{message}', [ChatController::class, 'file'])
    ->middleware($auth)
    ->name('api.v1.growya.chat.file');

Route::get('business-claims/{claim}/documents/{document}', [BusinessClaimController::class, 'document'])
    ->middleware($auth)
    ->name('api.v1.growya.claims.document');

Route::delete('roles/{role}/permissions/{permission}', [RoleController::class, 'removePermission'])
    ->middleware($admin)
    ->name('api.v1.roles.permissions.remove');

Route::patch('shops/{business}/cart/{item}', [CartCheckoutController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.growya.cart.update');

Route::delete('shops/{business}/cart/{item}', [CartCheckoutController::class, 'remove'])
    ->middleware($auth)
    ->name('api.v1.growya.cart.remove');

Route::get('businesses/{business}/orders/{order}', [BusinessOrderController::class, 'show'])
    ->middleware($auth)
    ->name('api.v1.businesses.orders.show');

Route::get('businesses/{business}/product-enquiries/{enquiry}', [ProductEnquiryController::class, 'show'])
    ->middleware($auth)
    ->name('api.v1.businesses.product-enquiries.show');

Route::put('businesses/{business}/staff/{member}', [BusinessStaffController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.businesses.staff.update');

Route::delete('businesses/{business}/staff/{member}', [BusinessStaffController::class, 'destroy'])
    ->middleware($auth)
    ->name('api.v1.businesses.staff.destroy');

Route::get('businesses/{business}/products/{product}', [ProductController::class, 'show'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.show');

Route::put('businesses/{business}/products/{product}', [ProductController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.update');

Route::delete('businesses/{business}/products/{product}', [ProductController::class, 'destroy'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.destroy');

Route::put('attributes/{attribute}/values/{value}', [AttributeController::class, 'updateValue'])
    ->middleware($auth)
    ->name('api.v1.attributes.values.update');

Route::delete('attributes/{attribute}/values/{value}', [AttributeController::class, 'destroyValue'])
    ->middleware($auth)
    ->name('api.v1.attributes.values.destroy');

Route::get('businesses/{business}/services/{service}', [ServiceController::class, 'show'])
    ->middleware($auth)
    ->name('api.v1.businesses.services.show');

Route::put('businesses/{business}/services/{service}', [ServiceController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.businesses.services.update');

Route::delete('businesses/{business}/services/{service}', [ServiceController::class, 'destroy'])
    ->middleware($auth)
    ->name('api.v1.businesses.services.destroy');

Route::get('businesses/{business}/service-enquiries/{enquiry}', [ServiceEnquiryController::class, 'show'])
    ->middleware($auth)
    ->name('api.v1.businesses.service-enquiries.show');

Route::get('businesses/{business}/appointments/{appointment}', [AppointmentController::class, 'show'])
    ->middleware($auth)
    ->name('api.v1.businesses.appointments.show');

Route::put('businesses/{business}/gallery/{gallery}', [BusinessGalleryController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.businesses.gallery.update');

Route::delete('businesses/{business}/gallery/{gallery}', [BusinessGalleryController::class, 'destroy'])
    ->middleware($auth)
    ->name('api.v1.businesses.gallery.destroy');

Route::put('businesses/{business}/offers/{offer}', [BusinessOfferController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.businesses.offers.update');

Route::delete('businesses/{business}/offers/{offer}', [BusinessOfferController::class, 'destroy'])
    ->middleware($auth)
    ->name('api.v1.businesses.offers.destroy');

Route::get('b/{slug}/products/{productSlug}', [PublicBusinessController::class, 'product'])
    ->middleware($public)
    ->name('api.v1.public.business.product');

Route::get('b/{slug}/services/{serviceSlug}', [PublicBusinessController::class, 'service'])
    ->middleware($public)
    ->name('api.v1.public.business.service');

Route::get('s/{business}/{service}/enquiry', [PublicServiceEnquiryController::class, 'create'])
    ->middleware($auth)
    ->name('api.v1.public.services.enquiries.create');

Route::post('s/{business}/{service}/enquiry', [PublicServiceEnquiryController::class, 'store'])
    ->middleware([...$auth, 'throttle:5,1'])
    ->name('api.v1.public.services.enquiries.store');

Route::get('businesses/{business}/catalog/{kind}', [TenantCatalogController::class, 'index'])
    ->middleware($auth)
    ->whereIn('kind', ['categories', 'attributes'])
    ->name('api.v1.businesses.catalog.index');

Route::post('businesses/{business}/catalog/{kind}', [TenantCatalogController::class, 'store'])
    ->middleware($auth)
    ->whereIn('kind', ['categories', 'attributes'])
    ->name('api.v1.businesses.catalog.store');

Route::get('businesses/{business}/products/bulk/template', [ProductBulkController::class, 'template'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.bulk.template');

Route::post('businesses/{business}/products/bulk/import', [ProductBulkController::class, 'import'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.bulk.import');

Route::get('businesses/{business}/products/bulk/export', [ProductBulkController::class, 'export'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.bulk.export');

Route::get('businesses/{business}/products/bulk/errors', [ProductBulkController::class, 'errorReport'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.bulk.errors');

Route::post('businesses/{business}/products/qr/bulk-print', [ProductQrCodeController::class, 'bulkPrint'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.qr.bulk-print');

Route::patch('businesses/{business}/crm-staff/{contact}/assign', [CrmStaffController::class, 'assign'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.staff.assign');

Route::patch('businesses/{business}/crm-staff/{contact}/progress', [CrmStaffController::class, 'progress'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.staff.progress');

Route::post('businesses/{business}/crm-staff/{contact}/activities', [CrmStaffController::class, 'activity'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.staff.activity');

Route::get('businesses/{business}/crm/{contact}/360', [CrmAdvancedController::class, 'show'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.advanced.show');

Route::get('businesses/{business}/crm/{contact}/edit', [BusinessCrmController::class, 'edit'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.edit');

Route::post('businesses/{business}/crm/{contact}/activities', [BusinessCrmController::class, 'note'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.note');

Route::patch('businesses/{business}/reviews/{review}/reply', [BusinessReviewController::class, 'reply'])
    ->middleware($auth)
    ->name('api.v1.businesses.reviews.reply');

Route::get('businesses/{business}/coupons/{coupon}/edit', [CouponController::class, 'edit'])
    ->middleware($auth)
    ->name('api.v1.businesses.coupons.edit');

Route::delete('admin/businesses/{business}/plan-overrides/{key}', [BusinessPlanOverrideController::class, 'remove'])
    ->middleware($admin)
    ->name('api.v1.growya.plan-overrides.remove');

Route::get('support/tickets/{ticket}/attachments/{attachment}', [SupportTicketController::class, 'attachment'])
    ->middleware($auth)
    ->name('api.v1.growya.support.attachment');

Route::get('my-orders/{order}/payment/{submission}/proof', [PaymentController::class, 'customerProof'])
    ->middleware($auth)
    ->name('api.v1.growya.payments.customer-proof');

Route::patch('businesses/{business}/returns/{returnRequest}/review', [OrderReturnsController::class, 'review'])
    ->middleware($auth)
    ->name('api.v1.businesses.returns.review');

Route::patch('businesses/{business}/returns/{returnRequest}/refund', [OrderReturnsController::class, 'markRefunded'])
    ->middleware($auth)
    ->name('api.v1.businesses.returns.refund');

Route::get('businesses/{business}/orders/{order}/delivery', [DeliveryTrackingController::class, 'sellerShow'])
    ->middleware($auth)
    ->name('api.v1.businesses.orders.delivery.show');

Route::patch('businesses/{business}/orders/{order}/delivery', [DeliveryTrackingController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.businesses.orders.delivery.update');

Route::patch('businesses/{business}/orders/{order}/status', [BusinessOrderController::class, 'status'])
    ->middleware($auth)
    ->name('api.v1.businesses.orders.status');

Route::patch('businesses/{business}/product-enquiries/{enquiry}/status', [ProductEnquiryController::class, 'status'])
    ->middleware($auth)
    ->name('api.v1.businesses.product-enquiries.status');

Route::post('businesses/{business}/product-enquiries/{enquiry}/notes', [ProductEnquiryController::class, 'note'])
    ->middleware($auth)
    ->name('api.v1.businesses.product-enquiries.notes.store');

Route::get('businesses/{business}/products/{product}/edit', [ProductController::class, 'edit'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.edit');

Route::get('businesses/{business}/products/{product}/gallery', [ProductImageController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.gallery.index');

Route::post('businesses/{business}/products/{product}/gallery', [ProductImageController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.gallery.store');

Route::get('businesses/{business}/products/{product}/variants', [ProductVariantController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.variants.index');

Route::post('businesses/{business}/products/{product}/variants', [ProductVariantController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.variants.store');

Route::get('businesses/{business}/products/{product}/tags', [ProductTagController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.tags.index');

Route::put('businesses/{business}/products/{product}/tags', [ProductTagController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.tags.update');

Route::get('businesses/{business}/products/{product}/attributes', [ProductAttributeController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.attributes.index');

Route::put('businesses/{business}/products/{product}/attributes', [ProductAttributeController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.attributes.update');

Route::get('businesses/{business}/products/{product}/inventory', [ProductInventoryController::class, 'index'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.inventory.index');

Route::post('businesses/{business}/products/{product}/inventory', [ProductInventoryController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.inventory.store');

Route::get('businesses/{business}/products/{product}/qr', [ProductQrCodeController::class, 'show'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.qr.show');

Route::post('businesses/{business}/products/{product}/qr', [ProductQrCodeController::class, 'generate'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.qr.generate');

Route::get('businesses/{business}/services/{service}/edit', [ServiceController::class, 'edit'])
    ->middleware($auth)
    ->name('api.v1.businesses.services.edit');

Route::post('businesses/{business}/services/{service}/images', [ServiceImageController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.businesses.services.images.store');

Route::patch('businesses/{business}/service-enquiries/{enquiry}/status', [ServiceEnquiryController::class, 'updateStatus'])
    ->middleware($auth)
    ->name('api.v1.businesses.service-enquiries.status');

Route::post('businesses/{business}/service-enquiries/{enquiry}/follow-ups', [ServiceEnquiryFollowUpController::class, 'store'])
    ->middleware($auth)
    ->name('api.v1.businesses.service-enquiries.follow-ups.store');

Route::patch('businesses/{business}/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])
    ->middleware($auth)
    ->name('api.v1.businesses.appointments.status');

Route::get('businesses/{business}/gallery/{gallery}/edit', [BusinessGalleryController::class, 'edit'])
    ->middleware($auth)
    ->name('api.v1.businesses.gallery.edit');

Route::get('businesses/{business}/offers/{offer}/edit', [BusinessOfferController::class, 'edit'])
    ->middleware($auth)
    ->name('api.v1.businesses.offers.edit');

Route::post('businesses/{business}/catalog-attributes/{attribute}/values', [TenantCatalogController::class, 'storeValue'])
    ->middleware($auth)
    ->name('api.v1.businesses.catalog.values.store');

Route::put('businesses/{business}/catalog/{kind}/{item}', [TenantCatalogController::class, 'update'])
    ->middleware($auth)
    ->whereIn('kind', ['categories', 'attributes'])
    ->name('api.v1.businesses.catalog.update');

Route::delete('businesses/{business}/catalog/{kind}/{item}', [TenantCatalogController::class, 'destroy'])
    ->middleware($auth)
    ->whereIn('kind', ['categories', 'attributes'])
    ->name('api.v1.businesses.catalog.destroy');

Route::patch('businesses/{business}/crm-incentives/rules/{rule}/toggle', [CrmIncentiveController::class, 'toggleRule'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.incentives.rule.toggle');

Route::patch('businesses/{business}/crm-incentives/claims/{claim}/review', [CrmIncentiveController::class, 'review'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.incentives.review');

Route::patch('businesses/{business}/crm-incentives/claims/{claim}/pay', [CrmIncentiveController::class, 'pay'])
    ->middleware($auth)
    ->name('api.v1.growya.crm.incentives.pay');

Route::get('businesses/{business}/products/{product}/qr/download', [ProductQrCodeController::class, 'download'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.qr.download');

Route::get('businesses/{business}/products/{product}/qr/print', [ProductQrCodeController::class, 'print'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.qr.print');

Route::put('businesses/{business}/products/{product}/gallery/{image}', [ProductImageController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.gallery.update');

Route::delete('businesses/{business}/products/{product}/gallery/{image}', [ProductImageController::class, 'destroy'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.gallery.destroy');

Route::put('businesses/{business}/products/{product}/variants/{variant}', [ProductVariantController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.variants.update');

Route::delete('businesses/{business}/products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.variants.destroy');

Route::delete('businesses/{business}/services/{service}/images/{image}', [ServiceImageController::class, 'destroy'])
    ->middleware($auth)
    ->name('api.v1.businesses.services.images.destroy');

Route::delete('businesses/{business}/catalog-attributes/{attribute}/values/{value}', [TenantCatalogController::class, 'destroyValue'])
    ->middleware($auth)
    ->name('api.v1.businesses.catalog.values.destroy');

Route::patch('businesses/{business}/orders/{order}/payment/{submission}/review', [PaymentController::class, 'review'])
    ->middleware($auth)
    ->name('api.v1.businesses.payments.review');

Route::get('businesses/{business}/orders/{order}/payment/{submission}/proof', [PaymentController::class, 'proof'])
    ->middleware($auth)
    ->name('api.v1.businesses.payments.proof');

Route::patch('businesses/{business}/product-enquiries/{enquiry}/notes/{note}/complete', [ProductEnquiryController::class, 'complete'])
    ->middleware($auth)
    ->name('api.v1.businesses.product-enquiries.notes.complete');

Route::post('businesses/{business}/products/{product}/gallery/{image}/featured', [ProductImageController::class, 'featured'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.gallery.featured');

Route::get('businesses/{business}/products/{product}/variants/{variant}/attributes', [ProductVariantAttributeController::class, 'edit'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.variants.attributes.edit');

Route::put('businesses/{business}/products/{product}/variants/{variant}/attributes', [ProductVariantAttributeController::class, 'update'])
    ->middleware($auth)
    ->name('api.v1.businesses.products.variants.attributes.update');

Route::patch('businesses/{business}/services/{service}/images/{image}/featured', [ServiceImageController::class, 'featured'])
    ->middleware($auth)
    ->name('api.v1.businesses.services.images.featured');

Route::patch('businesses/{business}/service-enquiries/{enquiry}/follow-ups/{followUp}/complete', [ServiceEnquiryFollowUpController::class, 'complete'])
    ->middleware($auth)
    ->name('api.v1.businesses.service-enquiries.follow-ups.complete');

/*
|--------------------------------------------------------------------------
| Extensions
|--------------------------------------------------------------------------
*/

Route::get('businesses/{business}/ai/plans', [AiPlanController::class, 'catalog'])
    ->middleware($ext(60))
    ->name('api.v1.extensions.ai.plans');

Route::post('businesses/{business}/ai/plan-requests', [AiPlanController::class, 'requestPlan'])
    ->middleware($ext(10))
    ->name('api.v1.extensions.ai.requests.store');

Route::get('admin/ai-plans', [AiPlanController::class, 'adminIndex'])
    ->middleware($ext(60))
    ->name('api.v1.extensions.ai.admin');

Route::post('admin/ai-plans', [AiPlanController::class, 'store'])
    ->middleware($ext(20))
    ->name('api.v1.extensions.ai.plans.store');

Route::put('admin/ai-plans/{plan}', [AiPlanController::class, 'update'])
    ->middleware($ext(20))
    ->name('api.v1.extensions.ai.plans.update');

Route::post('admin/ai-plan-requests/{purchase}/review', [AiPlanController::class, 'review'])
    ->middleware($ext(20))
    ->name('api.v1.extensions.ai.requests.review');

Route::get('businesses/{business}/ai/brand', [AiBrandController::class, 'index'])
    ->middleware($ext(60))
    ->name('api.v1.extensions.ai.brand');

Route::put('businesses/{business}/ai/brand', [AiBrandController::class, 'profile'])
    ->middleware($ext(20))
    ->name('api.v1.extensions.ai.brand.update');

Route::post('businesses/{business}/ai/assets', [AiBrandController::class, 'upload'])
    ->middleware($ext(20))
    ->name('api.v1.extensions.ai.assets.store');

Route::get('businesses/{business}/ai/assets/{asset}', [AiBrandController::class, 'asset'])
    ->middleware($ext(120))
    ->name('api.v1.extensions.ai.assets.show');

Route::delete('businesses/{business}/ai/assets/{asset}', [AiBrandController::class, 'deleteAsset'])
    ->middleware($ext(20))
    ->name('api.v1.extensions.ai.assets.delete');

Route::post('businesses/{business}/ai/presets', [AiBrandController::class, 'storePreset'])
    ->middleware($ext(20))
    ->name('api.v1.extensions.ai.presets.store');

Route::put('businesses/{business}/ai/presets/{preset}', [AiBrandController::class, 'updatePreset'])
    ->middleware($ext(20))
    ->name('api.v1.extensions.ai.presets.update');

Route::delete('businesses/{business}/ai/presets/{preset}', [AiBrandController::class, 'deletePreset'])
    ->middleware($ext(20))
    ->name('api.v1.extensions.ai.presets.delete');

Route::post('shops/{business}/quote', [QuoteController::class, 'quote'])
    ->middleware($ext(30))
    ->name('api.v1.extensions.checkout.quote');

Route::post('auth/otp/send', [OtpController::class, 'send'])
    ->middleware(['throttle:3,10'])
    ->name('api.v1.extensions.otp.send');

Route::post('auth/otp/verify', [OtpController::class, 'verify'])
    ->middleware(['throttle:10,10'])
    ->name('api.v1.extensions.otp.verify');

Route::get('devices', [DeviceController::class, 'index'])
    ->middleware($ext(60))
    ->name('api.v1.extensions.devices.index');

Route::post('devices', [DeviceController::class, 'store'])
    ->middleware($ext(20))
    ->name('api.v1.extensions.devices.store');

Route::delete('devices/{device}', [DeviceController::class, 'destroy'])
    ->middleware($ext(30))
    ->name('api.v1.extensions.devices.destroy');

Route::post('push-deliveries/{delivery}/ack', [DeviceController::class, 'acknowledge'])
    ->middleware($ext(120))
    ->name('api.v1.extensions.push.ack');

Route::post('activity-events', [ActivityController::class, 'store'])
    ->middleware($ext(30))
    ->name('api.v1.extensions.activity.store');

Route::get('recently-viewed', [ActivityController::class, 'history'])
    ->middleware($ext(60))
    ->name('api.v1.extensions.history.index');

Route::delete('recently-viewed', [ActivityController::class, 'clear'])
    ->middleware($ext(10))
    ->name('api.v1.extensions.history.clear');

Route::post('share-links', [ActivityController::class, 'share'])
    ->middleware($ext(20))
    ->name('api.v1.extensions.shares.store');

Route::get('share-links/{code}', [ActivityController::class, 'resolve'])
    ->middleware(['throttle:60,1', OptionalGrowyaToken::class])
    ->name('api.v1.extensions.shares.resolve');

Route::get('discovery/search', [ActivityController::class, 'search'])
    ->middleware(['throttle:30,1', OptionalGrowyaToken::class])
    ->name('api.v1.extensions.discovery.search');

Route::get('content', [AdminToolsController::class, 'publicContent'])
    ->middleware(['throttle:60,1', OptionalGrowyaToken::class])
    ->name('api.v1.extensions.content');

Route::post('reports', [AdminToolsController::class, 'report'])
    ->middleware($ext(5))
    ->name('api.v1.extensions.reports.store');

Route::post('reviews/{review}/photos', [ReviewPhotoController::class, 'store'])
    ->middleware($ext(10))
    ->name('api.v1.extensions.review-photos.store');

Route::delete('reviews/{review}/photos/{photo}', [ReviewPhotoController::class, 'destroy'])
    ->middleware($ext(20))
    ->name('api.v1.extensions.review-photos.destroy');

Route::get('review-photos/{photo}', [ReviewPhotoController::class, 'file'])
    ->middleware(['throttle:120,1', OptionalGrowyaToken::class])
    ->name('api.v1.extensions.review-photos.file');

Route::get('businesses/{business}/analytics/daily', [ActivityController::class, 'daily'])
    ->middleware($ext(60))
    ->name('api.v1.extensions.analytics.daily');

Route::get('businesses/{business}/ai', [AiController::class, 'index'])
    ->middleware($ext(60))
    ->name('api.v1.extensions.ai.index');

Route::post('businesses/{business}/ai', [AiController::class, 'store'])
    ->middleware(['throttle:ai-generation', 'auth:sanctum', EnsureActiveAccount::class, RequireGrowyaToken::class])
    ->name('api.v1.extensions.ai.store');

Route::get('businesses/{business}/ai/generations/{generation}', [AiController::class, 'show'])
    ->middleware($ext(60))
    ->name('api.v1.extensions.ai.show');

Route::get('businesses/{business}/ai/media/{media}', [AiController::class, 'media'])
    ->middleware($ext(60))
    ->name('api.v1.extensions.ai.media');

Route::get('businesses/{business}/ai/credits', [AiController::class, 'credits'])
    ->middleware($ext(60))
    ->name('api.v1.extensions.ai.credits');

Route::post('admin/businesses/{business}/ai/credits', [AiController::class, 'grant'])
    ->middleware($ext(20))
    ->name('api.v1.extensions.ai.grant');

Route::get('businesses/{business}/social/accounts', [SocialController::class, 'accounts'])
    ->middleware($ext(60))
    ->name('api.v1.extensions.social.accounts');

Route::post('businesses/{business}/social/connect', [SocialController::class, 'connect'])
    ->middleware($ext(5))
    ->name('api.v1.extensions.social.connect');

Route::get('businesses/{business}/social/candidates', [SocialController::class, 'candidates'])
    ->middleware($ext(30))
    ->name('api.v1.extensions.social.candidates');

Route::post('businesses/{business}/social/select', [SocialController::class, 'select'])
    ->middleware($ext(10))
    ->name('api.v1.extensions.social.select');

Route::delete('businesses/{business}/social/accounts/{account}', [SocialController::class, 'disconnect'])
    ->middleware($ext(20))
    ->name('api.v1.extensions.social.disconnect');

Route::get('businesses/{business}/social/posts', [SocialController::class, 'posts'])
    ->middleware($ext(60))
    ->name('api.v1.extensions.social.posts');

Route::post('businesses/{business}/social/posts', [SocialController::class, 'store'])
    ->middleware($ext(10))
    ->name('api.v1.extensions.social.store');

Route::post('businesses/{business}/social/posts/{post}/publish', [SocialController::class, 'publish'])
    ->middleware($ext(10))
    ->name('api.v1.extensions.social.publish');

Route::post('businesses/{business}/social/posts/{post}/cancel', [SocialController::class, 'cancel'])
    ->middleware($ext(10))
    ->name('api.v1.extensions.social.cancel');

Route::get('businesses/{business}/social/schedules', [SocialController::class, 'schedules'])
    ->middleware($ext(60))
    ->name('api.v1.extensions.social.schedules');

Route::post('businesses/{business}/social/schedules', [SocialController::class, 'schedule'])
    ->middleware($ext(10))
    ->name('api.v1.extensions.social.schedule');

Route::delete('businesses/{business}/social/schedules/{schedule}', [SocialController::class, 'stopSchedule'])
    ->middleware($ext(20))
    ->name('api.v1.extensions.social.stop-schedule');

Route::get('businesses/{business}/commerce-policy', [CommerceController::class, 'show'])
    ->middleware($ext(60))
    ->name('api.v1.extensions.commerce.show');

Route::put('businesses/{business}/commerce-policy', [CommerceController::class, 'save'])
    ->middleware($ext(10))
    ->name('api.v1.extensions.commerce.save');

Route::post('my-orders/{order}/gateway', [GatewayController::class, 'create'])
    ->middleware($ext(5))
    ->name('api.v1.extensions.gateway.create');

Route::post('my-orders/{order}/gateway/verify', [GatewayController::class, 'verify'])
    ->middleware($ext(10))
    ->name('api.v1.extensions.gateway.verify');

Route::get('businesses/{business}/gateway-payments', [GatewayController::class, 'index'])
    ->middleware($ext(60))
    ->name('api.v1.extensions.gateway.index');

Route::post('businesses/{business}/gateway-payments/reconcile', [GatewayController::class, 'reconcile'])
    ->middleware($ext(10))
    ->name('api.v1.extensions.gateway.reconcile');

Route::post('businesses/{business}/orders/{order}/delivery/send-otp', [DeliveryProofController::class, 'send'])
    ->middleware($ext(5))
    ->name('api.v1.extensions.delivery.send');

Route::post('businesses/{business}/orders/{order}/delivery/verify-otp', [DeliveryProofController::class, 'verify'])
    ->middleware($ext(10))
    ->name('api.v1.extensions.delivery.verify');

Route::post('businesses/{business}/orders/{order}/delivery/proof', [DeliveryProofController::class, 'upload'])
    ->middleware($ext(10))
    ->name('api.v1.extensions.delivery.upload');

Route::post('businesses/{business}/orders/{order}/delivery/complete', [DeliveryProofController::class, 'complete'])
    ->middleware($ext(20))
    ->name('api.v1.extensions.delivery.complete');

Route::get('orders/{order}/delivery-proof', [DeliveryProofController::class, 'file'])
    ->middleware($ext(60))
    ->name('api.v1.extensions.delivery.file');

Route::get('businesses/{business}/accounting/accounts', [AccountingController::class, 'accounts'])
    ->middleware($ext(60))
    ->name('api.v1.extensions.accounting.accounts');

Route::post('businesses/{business}/accounting/accounts', [AccountingController::class, 'account'])
    ->middleware($ext(20))
    ->name('api.v1.extensions.accounting.account');

Route::get('businesses/{business}/accounting/journals', [AccountingController::class, 'index'])
    ->middleware($ext(60))
    ->name('api.v1.extensions.accounting.index');

Route::post('businesses/{business}/accounting/journals', [AccountingController::class, 'store'])
    ->middleware($ext(20))
    ->name('api.v1.extensions.accounting.store');

Route::post('businesses/{business}/accounting/journals/{journal}/reverse', [AccountingController::class, 'reverse'])
    ->middleware($ext(10))
    ->name('api.v1.extensions.accounting.reverse');

Route::get('businesses/{business}/accounting/reports', [AccountingController::class, 'reports'])
    ->middleware($ext(30))
    ->name('api.v1.extensions.accounting.reports');

Route::get('admin/tools/{kind}', [AdminToolsController::class, 'index'])
    ->middleware($ext(60))
    ->name('api.v1.extensions.tools.index');

Route::post('admin/tools/{kind}', [AdminToolsController::class, 'save'])
    ->middleware($ext(20))
    ->name('api.v1.extensions.tools.store');

Route::put('admin/tools/{kind}/{item}', [AdminToolsController::class, 'save'])
    ->middleware($ext(20))
    ->name('api.v1.extensions.tools.update');

Route::delete('admin/tools/{kind}/{item}', [AdminToolsController::class, 'destroy'])
    ->middleware($ext(20))
    ->name('api.v1.extensions.tools.destroy');

Route::post('webhooks/razorpay', [GatewayController::class, 'webhook'])
    ->middleware($public)
    ->name('api.v1.extensions.razorpay.webhook');