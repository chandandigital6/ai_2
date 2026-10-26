<?php

use App\Http\Controllers\AdminBusinessReviewController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\BusinessFollowController;
use App\Http\Controllers\BusinessGalleryController;
use App\Http\Controllers\BusinessHourController;
use App\Http\Controllers\BusinessOfferController;
use App\Http\Controllers\BusinessReviewController;
use App\Http\Controllers\BusinessSocialLinkController;
use App\Http\Controllers\BusinessStaffController;
use App\Http\Controllers\BusinessVerificationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GrowyaAnalyticsController;
use App\Http\Controllers\GrowyaBusinessClaimController;
use App\Http\Controllers\GrowyaBusinessCrmController;
use App\Http\Controllers\GrowyaBusinessKycController;
use App\Http\Controllers\GrowyaBusinessOrderController;
use App\Http\Controllers\GrowyaBusinessOwnershipController;
use App\Http\Controllers\GrowyaBusinessPlanOverrideController;
use App\Http\Controllers\GrowyaBusinessRequestController;
use App\Http\Controllers\GrowyaCartCheckoutController;
use App\Http\Controllers\GrowyaChatController;
use App\Http\Controllers\GrowyaChatEnhancementController;
use App\Http\Controllers\GrowyaCouponController;
use App\Http\Controllers\GrowyaCrmAdvancedController;
use App\Http\Controllers\GrowyaCrmIncentiveController;
use App\Http\Controllers\GrowyaCrmKanbanController;
use App\Http\Controllers\GrowyaCrmStaffController;
use App\Http\Controllers\GrowyaCustomerAddressController;
use App\Http\Controllers\GrowyaDashboardController;
use App\Http\Controllers\GrowyaDeliveryTrackingController;
use App\Http\Controllers\GrowyaFinanceController;
use App\Http\Controllers\GrowyaLeadInsightsController;
use App\Http\Controllers\GrowyaLiveNotificationController;
use App\Http\Controllers\GrowyaNearbyController;
use App\Http\Controllers\GrowyaNotificationController;
use App\Http\Controllers\GrowyaOrderReturnsController;
use App\Http\Controllers\GrowyaPaidPromotionController;
use App\Http\Controllers\GrowyaPaymentController;
use App\Http\Controllers\GrowyaPlanController;
use App\Http\Controllers\GrowyaPlanEntitlementController;
use App\Http\Controllers\GrowyaReferralController;
use App\Http\Controllers\GrowyaReminderPreferenceController;
use App\Http\Controllers\GrowyaRoleMatrixController;
use App\Http\Controllers\GrowyaSubscriptionBillingController;
use App\Http\Controllers\GrowyaSubscriptionController;
use App\Http\Controllers\GrowyaSupportTicketController;
use App\Http\Controllers\GrowyaUserRoleController;
use App\Http\Controllers\GrowyaWebPushController;
use App\Http\Controllers\GrowyaWishlistController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductAttributeController;
use App\Http\Controllers\ProductBulkController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductEnquiryController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\ProductInventoryController;
use App\Http\Controllers\ProductQrCodeController;
use App\Http\Controllers\ProductTagController;
use App\Http\Controllers\ProductVariantAttributeController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\PublicBusinessController;
use App\Http\Controllers\PublicGlobalSearchController;
use App\Http\Controllers\PublicHomeController;
use App\Http\Controllers\PublicMarketplaceController;
use App\Http\Controllers\PublicOfferController;
use App\Http\Controllers\PublicOffersExploreController;
use App\Http\Controllers\PublicProductController;
use App\Http\Controllers\PublicServiceEnquiryController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ServiceEnquiryController;
use App\Http\Controllers\ServiceEnquiryFollowUpController;
use App\Http\Controllers\ServiceImageController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TenantCatalogController;
use App\Http\Middleware\GrowyaSuperAdminOnly;
use Illuminate\Support\Facades\Route;

Route::get('nearby-businesses', [GrowyaNearbyController::class, 'index'])->name('growya.nearby.index');

Route::get('my-referrals', [GrowyaReferralController::class, 'mine'])->middleware('auth')->name('growya.referrals.mine');
Route::get('admin/referrals', [GrowyaReferralController::class, 'admin'])->middleware('auth')->name('growya.referrals.admin');
Route::patch('admin/referrals/settings', [GrowyaReferralController::class, 'settings'])->middleware('auth')->name('growya.referrals.settings');
Route::post('/admin/referrals/{referral}/approve', [GrowyaReferralController::class, 'approveBusinessReward'])->middleware('auth')->name('growya.referrals.approve');

Route::get('b/{business:slug}/offers', [PublicOfferController::class, 'index'])->name('public.business.offers');

Route::get(
    'offers',
    [PublicOffersExploreController::class, 'index']
)->name('public.offers.index');

Route::get(
    'my-notifications/poll',
    [GrowyaLiveNotificationController::class, 'poll']
)->middleware(['auth', 'throttle:90,1'])
    ->name('growya.notifications.poll');

Route::get('my-notifications', [GrowyaNotificationController::class, 'index'])->middleware('auth')->name('growya.notifications.index');
Route::post('my-notifications/{recipient}/open', [GrowyaNotificationController::class, 'open'])->middleware('auth')->name('growya.notifications.open');
Route::patch('my-notifications/{recipient}/read', [GrowyaNotificationController::class, 'read'])->middleware('auth')->name('growya.notifications.read');
Route::patch('my-notifications/read-all', [GrowyaNotificationController::class, 'readAll'])->middleware('auth')->name('growya.notifications.read-all');

Route::get('products/{business}/{product}/enquiry', [ProductEnquiryController::class, 'create'])->middleware('auth')->name('public.products.enquiry.create');
Route::post('products/{business}/{product}/enquiry', [ProductEnquiryController::class, 'store'])->middleware(['auth', 'throttle:5,1'])->name('public.products.enquiry.store');

Route::get('b/{slug}/reviews', [BusinessReviewController::class, 'publicIndex'])
    ->name('public.business.reviews.index');

Route::get('promoted/{promotion}', [GrowyaPaidPromotionController::class, 'click'])->middleware('throttle:60,1')->name('growya.promotions.click');

// Authenticated explicit routes (no Route::resource)
Route::middleware('auth')->group(function () {

    Route::get('businesses/{business}/finance', [GrowyaFinanceController::class, 'index'])->name('growya.finance.index');
    Route::post('businesses/{business}/finance/categories', [GrowyaFinanceController::class, 'storeCategory'])->name('growya.finance.categories.store');
    Route::post('businesses/{business}/finance', [GrowyaFinanceController::class, 'store'])->name('growya.finance.store');
    Route::patch('businesses/{business}/finance/{entry}', [GrowyaFinanceController::class, 'update'])->name('growya.finance.update');
    Route::delete('businesses/{business}/finance/{entry}', [GrowyaFinanceController::class, 'destroy'])->name('growya.finance.destroy');
    Route::get('businesses/{business}/finance/export', [GrowyaFinanceController::class, 'export'])->name('growya.finance.export');

    Route::get('businesses/{business}/crm-incentives', [GrowyaCrmIncentiveController::class, 'index'])->name('growya.crm.incentives.index');
    Route::get('businesses/{business}/crm-incentives/report', [GrowyaCrmIncentiveController::class, 'report'])->name('growya.crm.incentives.report');
    Route::get('businesses/{business}/crm-incentives/export', [GrowyaCrmIncentiveController::class, 'export'])->name('growya.crm.incentives.export');
    Route::post('businesses/{business}/crm-incentives/rules', [GrowyaCrmIncentiveController::class, 'rule'])->name('growya.crm.incentives.rule');
    Route::patch('businesses/{business}/crm-incentives/rules/{rule}/toggle', [GrowyaCrmIncentiveController::class, 'toggleRule'])->name('growya.crm.incentives.rule.toggle');
    Route::post('businesses/{business}/crm-incentives/claims', [GrowyaCrmIncentiveController::class, 'claim'])->name('growya.crm.incentives.claim');
    Route::patch('businesses/{business}/crm-incentives/claims/{claim}/review', [GrowyaCrmIncentiveController::class, 'review'])->name('growya.crm.incentives.review');
    Route::patch('businesses/{business}/crm-incentives/claims/{claim}/pay', [GrowyaCrmIncentiveController::class, 'pay'])->name('growya.crm.incentives.pay');

    Route::get('businesses/{business}/crm-staff', [GrowyaCrmStaffController::class, 'dashboard'])->name('growya.crm.staff.dashboard');
    Route::get('businesses/{business}/crm-staff/report', [GrowyaCrmStaffController::class, 'report'])->name('growya.crm.staff.report');
    Route::post('businesses/{business}/crm-staff/targets', [GrowyaCrmStaffController::class, 'target'])->name('growya.crm.staff.target');
    Route::get('businesses/{business}/crm-staff/{contact}', [GrowyaCrmStaffController::class, 'show'])->name('growya.crm.staff.show');
    Route::patch('businesses/{business}/crm-staff/{contact}/assign', [GrowyaCrmStaffController::class, 'assign'])->name('growya.crm.staff.assign');
    Route::patch('businesses/{business}/crm-staff/{contact}/progress', [GrowyaCrmStaffController::class, 'progress'])->name('growya.crm.staff.progress');
    Route::post('businesses/{business}/crm-staff/{contact}/activities', [GrowyaCrmStaffController::class, 'activity'])->name('growya.crm.staff.activity');

    Route::get('businesses/{business}/crm-kanban', [GrowyaCrmKanbanController::class, 'index'])->name('growya.crm.kanban.index');
    Route::patch('businesses/{business}/crm-kanban/{contact}', [GrowyaCrmKanbanController::class, 'update'])->name('growya.crm.kanban.update');

    Route::get(
        'businesses/{business}/crm/{contact}/360',
        [GrowyaCrmAdvancedController::class, 'show']
    )->name('growya.crm.advanced.show');

    Route::get(
        'businesses/{business}/lead-insights',
        [GrowyaLeadInsightsController::class, 'owner']
    )->name('growya.lead-insights.owner');

    Route::get(
        'businesses/{business}/lead-insights/export',
        [GrowyaLeadInsightsController::class, 'ownerExport']
    )->name('growya.lead-insights.owner.export');

    Route::get(
        'admin/lead-insights',
        [GrowyaLeadInsightsController::class, 'admin']
    )->name('growya.lead-insights.admin');

    Route::get(
        'admin/lead-insights/export',
        [GrowyaLeadInsightsController::class, 'adminExport']
    )->name('growya.lead-insights.admin.export');

    Route::get('businesses/{business}/crm', [GrowyaBusinessCrmController::class, 'index'])->name('growya.crm.index');
    Route::get('businesses/{business}/crm/create', [GrowyaBusinessCrmController::class, 'create'])->name('growya.crm.create');
    Route::post('businesses/{business}/crm', [GrowyaBusinessCrmController::class, 'store'])->name('growya.crm.store');
    Route::get('businesses/{business}/crm/export', [GrowyaBusinessCrmController::class, 'export'])->name('growya.crm.export');
    Route::post('businesses/{business}/crm/import', [GrowyaBusinessCrmController::class, 'import'])->name('growya.crm.import');
    Route::get('businesses/{business}/crm/{contact}', [GrowyaBusinessCrmController::class, 'show'])->name('growya.crm.show');
    Route::get('businesses/{business}/crm/{contact}/edit', [GrowyaBusinessCrmController::class, 'edit'])->name('growya.crm.edit');
    Route::patch('businesses/{business}/crm/{contact}', [GrowyaBusinessCrmController::class, 'update'])->name('growya.crm.update');
    Route::post('businesses/{business}/crm/{contact}/activities', [GrowyaBusinessCrmController::class, 'note'])->name('growya.crm.note');

    Route::post('b/{slug}/reviews', [BusinessReviewController::class, 'store'])
        ->middleware('throttle:5,1')->name('public.business.reviews.store');
    Route::put('b/{slug}/reviews/{review}', [BusinessReviewController::class, 'update'])
        ->middleware('throttle:5,1')->name('public.business.reviews.update');
    Route::get('businesses/{business}/reviews', [BusinessReviewController::class, 'ownerIndex'])
        ->name('businesses.reviews.index');
    Route::patch('businesses/{business}/reviews/{review}/reply', [BusinessReviewController::class, 'reply'])
        ->name('businesses.reviews.reply');
    Route::get('admin/business-reviews', [AdminBusinessReviewController::class, 'index'])
        ->name('admin.business-reviews.index');
    Route::patch('admin/business-reviews/{review}/moderate', [AdminBusinessReviewController::class, 'update'])
        ->name('admin.business-reviews.update');

    Route::get('businesses/{business}/map-location', [GrowyaNearbyController::class, 'edit'])->name('growya.nearby.location.edit');
    Route::patch('businesses/{business}/map-location', [GrowyaNearbyController::class, 'update'])->name('growya.nearby.location.update');

    Route::get('my-reminder-preferences', [GrowyaReminderPreferenceController::class, 'edit'])->name('growya.reminders.preferences');
    Route::patch('my-reminder-preferences', [GrowyaReminderPreferenceController::class, 'update'])->name('growya.reminders.preferences.update');

    Route::get('my-wishlist', [GrowyaWishlistController::class, 'mine'])->name('growya.wishlist.mine');
    Route::post('my-wishlist/{type}/{id}', [GrowyaWishlistController::class, 'add'])->middleware('throttle:15,1')->whereNumber('id')->name('growya.wishlist.add');
    Route::delete('my-wishlist/{type}/{id}', [GrowyaWishlistController::class, 'remove'])->whereNumber('id')->name('growya.wishlist.remove');
    Route::get('businesses/{business}/wishlist-interests', [GrowyaWishlistController::class, 'seller'])->name('businesses.wishlist.index');
    Route::patch('businesses/{business}/wishlist-interests/{interest}', [GrowyaWishlistController::class, 'update'])->name('businesses.wishlist.update');
    Route::get('admin/wishlist-interests', [GrowyaWishlistController::class, 'admin'])->name('growya.admin.wishlist.index');
    Route::patch('admin/wishlist-interests/{interest}', [GrowyaWishlistController::class, 'adminUpdate'])->name('growya.admin.wishlist.update');

    Route::get('businesses/{business}/coupons', [GrowyaCouponController::class, 'index'])->name('businesses.coupons.index');
    Route::get('businesses/{business}/coupons/create', [GrowyaCouponController::class, 'create'])->name('businesses.coupons.create');
    Route::post('businesses/{business}/coupons', [GrowyaCouponController::class, 'store'])->name('businesses.coupons.store');
    Route::get('businesses/{business}/coupons/{coupon}/edit', [GrowyaCouponController::class, 'edit'])->name('businesses.coupons.edit');
    Route::patch('businesses/{business}/coupons/{coupon}', [GrowyaCouponController::class, 'update'])->name('businesses.coupons.update');

    Route::get('admin/subscription-plans', [GrowyaPlanController::class, 'index'])->name('growya.plans.index');
    Route::post('admin/subscription-plans', [GrowyaPlanController::class, 'store'])->name('growya.plans.store');
    Route::patch('admin/subscription-plans/{plan}', [GrowyaPlanController::class, 'update'])->name('growya.plans.update');
    Route::get('admin/subscriptions', [GrowyaSubscriptionController::class, 'adminIndex'])->name('growya.subscriptions.admin');
    Route::patch('admin/subscriptions/{subscription}/review', [GrowyaSubscriptionController::class, 'review'])->name('growya.subscriptions.review');
    Route::get('businesses/{business}/subscriptions', [GrowyaSubscriptionController::class, 'index'])->name('growya.subscriptions.business');
    Route::post('businesses/{business}/subscriptions', [GrowyaSubscriptionController::class, 'request'])->middleware('throttle:5,1')->name('growya.subscriptions.request');

    Route::get('admin/subscription-plans/{plan}/limits', [GrowyaPlanEntitlementController::class, 'edit'])->name('growya.entitlements.edit');
    Route::patch('admin/subscription-plans/{plan}/limits', [GrowyaPlanEntitlementController::class, 'update'])->name('growya.entitlements.update');
    Route::get('businesses/{business}/plan-usage', [GrowyaPlanEntitlementController::class, 'usage'])->name('growya.entitlements.usage');

    Route::get('admin/businesses/{business}/plan-overrides', [GrowyaBusinessPlanOverrideController::class, 'edit'])->name('growya.plan-overrides.edit');
    Route::post('admin/businesses/{business}/plan-overrides', [GrowyaBusinessPlanOverrideController::class, 'save'])->name('growya.plan-overrides.save');
    Route::delete('admin/businesses/{business}/plan-overrides/{key}', [GrowyaBusinessPlanOverrideController::class, 'remove'])->name('growya.plan-overrides.remove');

    Route::get('admin/paid-promotion-packages', [GrowyaPaidPromotionController::class, 'packages'])->name('growya.promotions.packages');
    Route::post('admin/paid-promotion-packages', [GrowyaPaidPromotionController::class, 'storePackage'])->name('growya.promotions.packages.store');
    Route::patch('admin/paid-promotion-packages/{package}', [GrowyaPaidPromotionController::class, 'updatePackage'])->name('growya.promotions.packages.update');
    Route::get('admin/paid-promotions', [GrowyaPaidPromotionController::class, 'adminIndex'])->name('growya.promotions.admin');
    Route::patch('admin/paid-promotions/{promotion}', [GrowyaPaidPromotionController::class, 'review'])->name('growya.promotions.review');
    Route::get('businesses/{business}/paid-promotions', [GrowyaPaidPromotionController::class, 'ownerIndex'])->name('growya.promotions.owner');
    Route::post('businesses/{business}/paid-promotions', [GrowyaPaidPromotionController::class, 'request'])->middleware('throttle:5,1')->name('growya.promotions.owner.request');

    Route::get('businesses/{business}/analytics', [GrowyaAnalyticsController::class, 'owner'])
        ->name('growya.analytics.owner');
    Route::get('businesses/{business}/analytics/export', [GrowyaAnalyticsController::class, 'ownerExport'])
        ->name('growya.analytics.owner.export');
    Route::get('admin/analytics', [GrowyaAnalyticsController::class, 'admin'])
        ->name('growya.analytics.admin');
    Route::get('admin/analytics/export', [GrowyaAnalyticsController::class, 'adminExport'])
        ->name('growya.analytics.admin.export');

    Route::get('support/tickets', [GrowyaSupportTicketController::class, 'index'])->name('growya.support.index');
    Route::get('support/tickets/create', [GrowyaSupportTicketController::class, 'create'])->name('growya.support.create');
    Route::post('support/tickets', [GrowyaSupportTicketController::class, 'store'])->middleware('throttle:5,1')->name('growya.support.store');
    Route::get('support/tickets/{ticket}', [GrowyaSupportTicketController::class, 'show'])->name('growya.support.show');
    Route::post('support/tickets/{ticket}/reply', [GrowyaSupportTicketController::class, 'reply'])->middleware('throttle:15,1')->name('growya.support.reply');
    Route::patch('support/tickets/{ticket}/status', [GrowyaSupportTicketController::class, 'update'])->name('growya.support.update');
    Route::patch('support/tickets/{ticket}/assign', [GrowyaSupportTicketController::class, 'assign'])->name('growya.support.assign');
    Route::get('support/tickets/{ticket}/attachments/{attachment}', [GrowyaSupportTicketController::class, 'attachment'])->name('growya.support.attachment');

    Route::get('businesses/{business}/kyc', [GrowyaBusinessKycController::class, 'owner'])->name('growya.kyc.owner');
    Route::post('businesses/{business}/kyc', [GrowyaBusinessKycController::class, 'submit'])->middleware('throttle:3,1')->name('growya.kyc.submit');
    Route::get('admin/business-kyc', [GrowyaBusinessKycController::class, 'admin'])->name('growya.kyc.admin');
    Route::get('admin/business-kyc/{submission}', [GrowyaBusinessKycController::class, 'adminShow'])->name('growya.kyc.admin.show');
    Route::patch('admin/business-kyc/{submission}', [GrowyaBusinessKycController::class, 'review'])->name('growya.kyc.review');
    Route::get('business-kyc/{submission}/documents/{document}', [GrowyaBusinessKycController::class, 'download'])->name('growya.kyc.download');

    Route::get('my-messages', [GrowyaChatController::class, 'mine'])->name('growya.chat.mine');
    Route::get('businesses/{business}/messages', [GrowyaChatController::class, 'inbox'])->name('growya.chat.inbox');
    Route::post('businesses/{business}/messages/start', [GrowyaChatController::class, 'start'])->middleware('throttle:15,1')->name('growya.chat.start');
    Route::get('messages/{thread}', [GrowyaChatController::class, 'show'])->name('growya.chat.show');
    Route::get('messages/{thread}/poll', [GrowyaChatController::class, 'poll'])->middleware('throttle:120,1')->name('growya.chat.poll');
    Route::post('messages/{thread}', [GrowyaChatController::class, 'send'])->middleware('throttle:60,1')->name('growya.chat.send');
    Route::get('messages/{thread}/files/{message}', [GrowyaChatController::class, 'file'])->name('growya.chat.file');

    Route::patch('messages/{thread}/assign', [GrowyaChatEnhancementController::class, 'assign'])->name('growya.chat.assign');
    Route::patch('messages/{thread}/block', [GrowyaChatEnhancementController::class, 'block'])->name('growya.chat.block');
    Route::post('messages/{thread}/report', [GrowyaChatEnhancementController::class, 'report'])->middleware('throttle:5,1')->name('growya.chat.report');
    Route::patch('messages/{thread}/followup', [GrowyaChatEnhancementController::class, 'followup'])->name('growya.chat.followup');
    Route::get('messages/{thread}/activity', [GrowyaChatEnhancementController::class, 'activity'])->name('growya.chat.activity');
    Route::get('admin/chat-reports', [GrowyaChatEnhancementController::class, 'reports'])->name('growya.chat.reports');
    Route::patch('admin/chat-reports/{report}', [GrowyaChatEnhancementController::class, 'review'])->name('growya.chat.reports.review');
    Route::get('my-messages/unread-count', [GrowyaChatEnhancementController::class, 'unread'])->middleware('throttle:90,1')->name('growya.chat.unread');

    Route::get('businesses/{business}/claim', [GrowyaBusinessClaimController::class, 'create'])->name('growya.claims.create');
    Route::post('businesses/{business}/claim', [GrowyaBusinessClaimController::class, 'store'])->middleware('throttle:3,1')->name('growya.claims.store');
    Route::get('my-business-claims', [GrowyaBusinessClaimController::class, 'mine'])->name('growya.claims.mine');
    Route::get('business-claims/{claim}', [GrowyaBusinessClaimController::class, 'show'])->name('growya.claims.show');
    Route::get('business-claims/{claim}/documents/{document}', [GrowyaBusinessClaimController::class, 'document'])->name('growya.claims.document');
    Route::get('admin/business-claims', [GrowyaBusinessClaimController::class, 'admin'])->name('growya.claims.admin');
    Route::patch('admin/business-claims/{claim}/review', [GrowyaBusinessClaimController::class, 'review'])->name('growya.claims.review');

    Route::get('admin/subscription-billing', [GrowyaSubscriptionBillingController::class, 'adminIndex'])->name('growya.billing.admin');
    Route::post('admin/subscription-billing/payments/{payment}', [GrowyaSubscriptionBillingController::class, 'issue'])->middleware('throttle:10,1')->name('growya.billing.issue');
    Route::get('admin/subscription-billing/export', [GrowyaSubscriptionBillingController::class, 'export'])->name('growya.billing.export');
    Route::get('businesses/{business}/billing', [GrowyaSubscriptionBillingController::class, 'ownerIndex'])->name('growya.billing.owner');
    Route::get('subscription-billing/{document}', [GrowyaSubscriptionBillingController::class, 'show'])->name('growya.billing.show');
    Route::get('subscription-billing/{document}/pdf', [GrowyaSubscriptionBillingController::class, 'pdf'])->name('growya.billing.pdf');
});

Route::middleware('auth')->group(function () {
    Route::get(
        'my-following',
        [BusinessFollowController::class, 'index']
    )->name('public.business.following');

    Route::post(
        'businesses/{business}/follow',
        [BusinessFollowController::class, 'store']
    )->name('public.business.follow');

    Route::delete(
        'businesses/{business}/follow',
        [BusinessFollowController::class, 'destroy']
    )->name('public.business.unfollow');
});

Route::get('/', [PublicHomeController::class, 'index'])->name('home');

Route::get(
    '/marketplace',
    [PublicMarketplaceController::class, 'index']
)->name('public.marketplace.index');

Route::get(
    'search',
    [PublicGlobalSearchController::class, 'index']
)->name('public.search.index');

Route::get(
    'search/suggestions',
    [PublicGlobalSearchController::class, 'suggest']
)->middleware('throttle:30,1')
    ->name('public.search.suggest');

Route::middleware(['auth', 'verified'])->group(function () {
    // Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get(
        'dashboard',
        [GrowyaDashboardController::class, 'index']
    )->name('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('business-applications', [GrowyaBusinessRequestController::class, 'index'])
        ->name('growya.business-request.status');

    Route::middleware(GrowyaSuperAdminOnly::class)->group(function () {
        Route::resource('roles', RoleController::class);
        Route::delete('roles/{role}/permissions/{permission}', [RoleController::class, 'removePermission'])
            ->name('roles.permissions.remove');

        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::post('permissions/assign', [PermissionController::class, 'assign'])->name('permissions.assign');
        Route::post('permissions/store', [PermissionController::class, 'store'])->name('permissions.store');
        Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');
        Route::get('admin/access-control', [GrowyaRoleMatrixController::class, 'index'])->name('growya.roles.matrix');
        Route::patch('admin/access-control/roles/{role}', [GrowyaRoleMatrixController::class, 'update'])->name('growya.roles.matrix.update');
    });

    Route::get('admin/user-roles', [GrowyaUserRoleController::class, 'index'])
        ->name('growya.users.roles.index');
    Route::patch('admin/user-roles/{user}', [GrowyaUserRoleController::class, 'update'])
        ->name('growya.users.roles.update');

    Route::get('admin/business-ownership', [GrowyaBusinessOwnershipController::class, 'index'])
        ->name('growya.ownership.index');
    Route::get('admin/business-ownership/{business}/edit', [GrowyaBusinessOwnershipController::class, 'edit'])
        ->name('growya.ownership.edit');
    Route::patch('admin/business-ownership/{business}', [GrowyaBusinessOwnershipController::class, 'update'])
        ->middleware('throttle:10,1')->name('growya.ownership.update');

    Route::get('/users', function () {
        abort_unless(auth()->user()?->hasRole('super_admin', 'web'), 403);

        return view('users.index');
    })->name('users.index');

    Route::get('my-orders/{order}/payment', [GrowyaPaymentController::class, 'customerIndex'])->name('growya.payments.customer');
    Route::post('my-orders/{order}/payment', [GrowyaPaymentController::class, 'submit'])->middleware('throttle:5,1')->name('growya.payments.submit');
    Route::get('my-orders/{order}/payment/{submission}/proof', [GrowyaPaymentController::class, 'customerProof'])->name('growya.payments.customer-proof');
    Route::get('businesses/{business}/payments', [GrowyaPaymentController::class, 'sellerIndex'])->name('businesses.payments.index');
    Route::patch('businesses/{business}/orders/{order}/payment/{submission}/review', [GrowyaPaymentController::class, 'review'])->name('businesses.payments.review');
    Route::get('businesses/{business}/orders/{order}/payment/{submission}/proof', [GrowyaPaymentController::class, 'proof'])->name('businesses.payments.proof');

    Route::get('my-orders/{order}/returns', [GrowyaOrderReturnsController::class, 'customerIndex'])->name('growya.returns.customer');
    Route::post('my-orders/{order}/returns', [GrowyaOrderReturnsController::class, 'submit'])->middleware('throttle:5,1')->name('growya.returns.submit');
    Route::get('businesses/{business}/returns', [GrowyaOrderReturnsController::class, 'sellerIndex'])->name('businesses.returns.index');
    Route::patch('businesses/{business}/returns/{returnRequest}/review', [GrowyaOrderReturnsController::class, 'review'])->name('businesses.returns.review');
    Route::patch('businesses/{business}/returns/{returnRequest}/refund', [GrowyaOrderReturnsController::class, 'markRefunded'])->name('businesses.returns.refund');

    Route::get('my-orders/{order}/tracking', [GrowyaDeliveryTrackingController::class, 'customer'])->name('growya.orders.tracking');
    Route::get('businesses/{business}/delivery', [GrowyaDeliveryTrackingController::class, 'index'])->name('businesses.delivery.index');
    Route::get('businesses/{business}/orders/{order}/delivery', [GrowyaDeliveryTrackingController::class, 'sellerShow'])->name('businesses.orders.delivery.show');
    Route::patch('businesses/{business}/orders/{order}/delivery', [GrowyaDeliveryTrackingController::class, 'update'])->name('businesses.orders.delivery.update');

    Route::get('shops/{business}/cart', [GrowyaCartCheckoutController::class, 'index'])->name('growya.cart.index');
    Route::post('shops/{business}/cart', [GrowyaCartCheckoutController::class, 'add'])->name('growya.cart.add');
    Route::patch('shops/{business}/cart/{item}', [GrowyaCartCheckoutController::class, 'update'])->name('growya.cart.update');
    Route::delete('shops/{business}/cart/{item}', [GrowyaCartCheckoutController::class, 'remove'])->name('growya.cart.remove');
    Route::get('shops/{business}/checkout', [GrowyaCartCheckoutController::class, 'checkoutForm'])->name('growya.checkout.form');
    Route::post('shops/{business}/checkout', [GrowyaCartCheckoutController::class, 'place'])->name('growya.checkout.place');
    Route::get('my-orders', [GrowyaCartCheckoutController::class, 'myOrders'])->name('growya.orders.mine');
    Route::get('my-orders/{order}', [GrowyaCartCheckoutController::class, 'order'])->name('growya.orders.show');
    Route::get('businesses/{business}/orders', [GrowyaBusinessOrderController::class, 'index'])->name('businesses.orders.index');
    Route::get('businesses/{business}/orders/{order}', [GrowyaBusinessOrderController::class, 'show'])->name('businesses.orders.show');
    Route::patch('businesses/{business}/orders/{order}/status', [GrowyaBusinessOrderController::class, 'status'])->name('businesses.orders.status');

    Route::get('my-addresses', [GrowyaCustomerAddressController::class, 'index'])->name('growya.addresses.index');
    Route::post('my-addresses', [GrowyaCustomerAddressController::class, 'store'])->name('growya.addresses.store');
    Route::put('my-addresses/{address}', [GrowyaCustomerAddressController::class, 'update'])->name('growya.addresses.update');
    Route::patch('my-addresses/{address}/default', [GrowyaCustomerAddressController::class, 'setDefault'])->name('growya.addresses.default');
    Route::delete('my-addresses/{address}', [GrowyaCustomerAddressController::class, 'destroy'])->name('growya.addresses.destroy');

    Route::get('businesses/{business}/product-enquiries', [ProductEnquiryController::class, 'index'])->name('businesses.product-enquiries.index');
    Route::get('businesses/{business}/product-enquiries/{enquiry}', [ProductEnquiryController::class, 'show'])->name('businesses.product-enquiries.show');
    Route::patch('businesses/{business}/product-enquiries/{enquiry}/status', [ProductEnquiryController::class, 'status'])->name('businesses.product-enquiries.status');
    Route::post('businesses/{business}/product-enquiries/{enquiry}/notes', [ProductEnquiryController::class, 'note'])->name('businesses.product-enquiries.notes.store');
    Route::patch('businesses/{business}/product-enquiries/{enquiry}/notes/{note}/complete', [ProductEnquiryController::class, 'complete'])->name('businesses.product-enquiries.notes.complete');

    /*
|--------------------------------------------------------------------------
| Growya Business Management
|--------------------------------------------------------------------------
*/

    Route::get('businesses', [BusinessController::class, 'index'])
        ->name('businesses.index');

    Route::get('businesses/create', [BusinessController::class, 'create'])
        ->name('businesses.create');

    Route::post('businesses', [BusinessController::class, 'store'])
        ->name('businesses.store');

    Route::get('businesses/{business}', [BusinessController::class, 'show'])
        ->name('businesses.show');

    Route::get('businesses/{business}/edit', [BusinessController::class, 'edit'])
        ->name('businesses.edit');

    Route::put('businesses/{business}', [BusinessController::class, 'update'])
        ->name('businesses.update');

    Route::delete('businesses/{business}', [BusinessController::class, 'destroy'])
        ->name('businesses.destroy');

    Route::get(
        'businesses/{business}/hours',
        [BusinessHourController::class, 'index']
    )->name('businesses.hours.index');

    Route::post(
        'businesses/{business}/hours',
        [BusinessHourController::class, 'store']
    )->name('businesses.hours.store');

    /*
|--------------------------------------------------------------------------
| Business Social Links
|--------------------------------------------------------------------------
*/

    Route::get(
        'businesses/{business}/social-links',
        [BusinessSocialLinkController::class, 'index']
    )->name('businesses.social-links.index');

    Route::post(
        'businesses/{business}/social-links',
        [BusinessSocialLinkController::class, 'store']
    )->name('businesses.social-links.store');

    /*
|--------------------------------------------------------------------------
| Growya Business Staff Management
|--------------------------------------------------------------------------
*/

    Route::get(
        'businesses/{business}/staff',
        [BusinessStaffController::class, 'index']
    )->name('businesses.staff.index');

    Route::post(
        'businesses/{business}/staff',
        [BusinessStaffController::class, 'store']
    )->name('businesses.staff.store');

    Route::put(
        'businesses/{business}/staff/{member}',
        [BusinessStaffController::class, 'update']
    )->name('businesses.staff.update');

    Route::delete(
        'businesses/{business}/staff/{member}',
        [BusinessStaffController::class, 'destroy']
    )->name('businesses.staff.destroy');

    Route::get('businesses/{business}/staff/history', [BusinessStaffController::class, 'history'])
        ->name('businesses.staff.history');

    /*
|--------------------------------------------------------------------------
| Growya Category Management
|--------------------------------------------------------------------------
*/

    Route::get('categories', [CategoryController::class, 'index'])
        ->name('categories.index');

    Route::get('categories/create', [CategoryController::class, 'create'])
        ->name('categories.create');

    Route::post('categories', [CategoryController::class, 'store'])
        ->name('categories.store');

    Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])
        ->name('categories.edit');

    Route::put('categories/{category}', [CategoryController::class, 'update'])
        ->name('categories.update');

    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])
        ->name('categories.destroy');

    /*
|--------------------------------------------------------------------------
| Product Bulk CSV Import / Export
|--------------------------------------------------------------------------
*/

    Route::get(
        'businesses/{business}/products/bulk',
        [ProductBulkController::class, 'index']
    )->name('businesses.products.bulk.index');

    Route::get(
        'businesses/{business}/products/bulk/template',
        [ProductBulkController::class, 'template']
    )->name('businesses.products.bulk.template');

    Route::post(
        'businesses/{business}/products/bulk/import',
        [ProductBulkController::class, 'import']
    )->name('businesses.products.bulk.import');

    Route::get(
        'businesses/{business}/products/bulk/export',
        [ProductBulkController::class, 'export']
    )->name('businesses.products.bulk.export');

    Route::get(
        'businesses/{business}/products/bulk/errors',
        [ProductBulkController::class, 'errorReport']
    )->name('businesses.products.bulk.errors');

    // Growya Products Management

    Route::get(
        'businesses/{business}/products',
        [ProductController::class, 'index']
    )->name('businesses.products.index');

    Route::get(
        'businesses/{business}/products/create',
        [ProductController::class, 'create']
    )->name('businesses.products.create');

    Route::post(
        'businesses/{business}/products',
        [ProductController::class, 'store']
    )->name('businesses.products.store');

    Route::get(
        'businesses/{business}/products/{product}',
        [ProductController::class, 'show']
    )->name('businesses.products.show');

    Route::get(
        'businesses/{business}/products/{product}/edit',
        [ProductController::class, 'edit']
    )->name('businesses.products.edit');

    Route::put(
        'businesses/{business}/products/{product}',
        [ProductController::class, 'update']
    )->name('businesses.products.update');

    Route::delete(
        'businesses/{business}/products/{product}',
        [ProductController::class, 'destroy']
    )->name('businesses.products.destroy');

    /*
|--------------------------------------------------------------------------
| Product Gallery Management
|--------------------------------------------------------------------------
*/

    Route::get(
        'businesses/{business}/products/{product}/gallery',
        [ProductImageController::class, 'index']
    )->name('businesses.products.gallery.index');

    Route::post(
        'businesses/{business}/products/{product}/gallery',
        [ProductImageController::class, 'store']
    )->name('businesses.products.gallery.store');

    Route::put(
        'businesses/{business}/products/{product}/gallery/{image}',
        [ProductImageController::class, 'update']
    )->name('businesses.products.gallery.update');

    Route::post(
        'businesses/{business}/products/{product}/gallery/{image}/featured',
        [ProductImageController::class, 'featured']
    )->name('businesses.products.gallery.featured');

    Route::delete(
        'businesses/{business}/products/{product}/gallery/{image}',
        [ProductImageController::class, 'destroy']
    )->name('businesses.products.gallery.destroy');

    /*
|--------------------------------------------------------------------------
| Growya Product Variants
|--------------------------------------------------------------------------
*/

    Route::get(
        'businesses/{business}/products/{product}/variants',
        [ProductVariantController::class, 'index']
    )->name('businesses.products.variants.index');

    Route::post(
        'businesses/{business}/products/{product}/variants',
        [ProductVariantController::class, 'store']
    )->name('businesses.products.variants.store');

    Route::put(
        'businesses/{business}/products/{product}/variants/{variant}',
        [ProductVariantController::class, 'update']
    )->name('businesses.products.variants.update');

    Route::delete(
        'businesses/{business}/products/{product}/variants/{variant}',
        [ProductVariantController::class, 'destroy']
    )->name('businesses.products.variants.destroy');

    /*
|--------------------------------------------------------------------------
| Global Tags Management
|--------------------------------------------------------------------------
*/

    Route::get(
        'tags',
        [TagController::class, 'index']
    )->name('tags.index');

    Route::post(
        'tags',
        [TagController::class, 'store']
    )->name('tags.store');

    Route::put(
        'tags/{tag}',
        [TagController::class, 'update']
    )->name('tags.update');

    Route::delete(
        'tags/{tag}',
        [TagController::class, 'destroy']
    )->name('tags.destroy');

    /*
|--------------------------------------------------------------------------
| Product Tags Management
|--------------------------------------------------------------------------
*/

    Route::get(
        'businesses/{business}/products/{product}/tags',
        [ProductTagController::class, 'index']
    )->name('businesses.products.tags.index');

    Route::put(
        'businesses/{business}/products/{product}/tags',
        [ProductTagController::class, 'update']
    )->name('businesses.products.tags.update');

    /*
|--------------------------------------------------------------------------
| Global Attributes & Values
|--------------------------------------------------------------------------
*/

    Route::get(
        'attributes',
        [AttributeController::class, 'index']
    )->name('attributes.index');

    Route::post(
        'attributes',
        [AttributeController::class, 'store']
    )->name('attributes.store');

    Route::put(
        'attributes/{attribute}',
        [AttributeController::class, 'update']
    )->name('attributes.update');

    Route::delete(
        'attributes/{attribute}',
        [AttributeController::class, 'destroy']
    )->name('attributes.destroy');

    Route::post(
        'attributes/{attribute}/values',
        [AttributeController::class, 'storeValue']
    )->name('attributes.values.store');

    Route::put(
        'attributes/{attribute}/values/{value}',
        [AttributeController::class, 'updateValue']
    )->name('attributes.values.update');

    Route::delete(
        'attributes/{attribute}/values/{value}',
        [AttributeController::class, 'destroyValue']
    )->name('attributes.values.destroy');

    /*
|--------------------------------------------------------------------------
| Product Attribute Assignment
|--------------------------------------------------------------------------
*/

    Route::get(
        'businesses/{business}/products/{product}/attributes',
        [ProductAttributeController::class, 'index']
    )->name('businesses.products.attributes.index');

    Route::put(
        'businesses/{business}/products/{product}/attributes',
        [ProductAttributeController::class, 'update']
    )->name('businesses.products.attributes.update');

    /*
|--------------------------------------------------------------------------
| Product Variant Attribute Mapping
|--------------------------------------------------------------------------
*/

    Route::get(
        'businesses/{business}/products/{product}/variants/{variant}/attributes',
        [ProductVariantAttributeController::class, 'edit']
    )->name('businesses.products.variants.attributes.edit');

    Route::put(
        'businesses/{business}/products/{product}/variants/{variant}/attributes',
        [ProductVariantAttributeController::class, 'update']
    )->name('businesses.products.variants.attributes.update');

    /*
|--------------------------------------------------------------------------
| Growya Product Inventory
|--------------------------------------------------------------------------
*/

    Route::get(
        'businesses/{business}/products/{product}/inventory',
        [ProductInventoryController::class, 'index']
    )->name('businesses.products.inventory.index');

    Route::post(
        'businesses/{business}/products/{product}/inventory',
        [ProductInventoryController::class, 'store']
    )->name('businesses.products.inventory.store');

    /*
|--------------------------------------------------------------------------
| Product QR Code Management
|--------------------------------------------------------------------------
*/

    Route::get(
        'businesses/{business}/products/{product}/qr',
        [ProductQrCodeController::class, 'show']
    )->name('businesses.products.qr.show');

    Route::post(
        'businesses/{business}/products/{product}/qr',
        [ProductQrCodeController::class, 'generate']
    )->name('businesses.products.qr.generate');

    Route::get(
        'businesses/{business}/products/{product}/qr/download',
        [ProductQrCodeController::class, 'download']
    )->name('businesses.products.qr.download');

    Route::get(
        'businesses/{business}/products/{product}/qr/print',
        [ProductQrCodeController::class, 'print']
    )->name('businesses.products.qr.print');

    Route::post(
        'businesses/{business}/products/qr/bulk-print',
        [ProductQrCodeController::class, 'bulkPrint']
    )->name('businesses.products.qr.bulk-print');

    /*
|--------------------------------------------------------------------------
| Growya Services CRUD
|--------------------------------------------------------------------------
*/

    Route::get(
        'businesses/{business}/services',
        [ServiceController::class, 'index']
    )->name('businesses.services.index');

    Route::get(
        'businesses/{business}/services/create',
        [ServiceController::class, 'create']
    )->name('businesses.services.create');

    Route::post(
        'businesses/{business}/services',
        [ServiceController::class, 'store']
    )->name('businesses.services.store');

    Route::get(
        'businesses/{business}/services/{service}',
        [ServiceController::class, 'show']
    )->name('businesses.services.show');

    Route::get(
        'businesses/{business}/services/{service}/edit',
        [ServiceController::class, 'edit']
    )->name('businesses.services.edit');

    Route::put(
        'businesses/{business}/services/{service}',
        [ServiceController::class, 'update']
    )->name('businesses.services.update');

    Route::delete(
        'businesses/{business}/services/{service}',
        [ServiceController::class, 'destroy']
    )->name('businesses.services.destroy');

    /*
|--------------------------------------------------------------------------
| Service Gallery
|--------------------------------------------------------------------------
*/

    Route::post(
        'businesses/{business}/services/{service}/images',
        [ServiceImageController::class, 'store']
    )->name('businesses.services.images.store');

    Route::patch(
        'businesses/{business}/services/{service}/images/{image}/featured',
        [ServiceImageController::class, 'featured']
    )->name('businesses.services.images.featured');

    Route::delete(
        'businesses/{business}/services/{service}/images/{image}',
        [ServiceImageController::class, 'destroy']
    )->name('businesses.services.images.destroy');

    /*
|--------------------------------------------------------------------------
| Business Service Enquiries
|--------------------------------------------------------------------------
*/

    Route::get(
        'businesses/{business}/service-enquiries',
        [ServiceEnquiryController::class, 'index']
    )->name('businesses.service-enquiries.index');

    Route::get(
        'businesses/{business}/service-enquiries/{enquiry}',
        [ServiceEnquiryController::class, 'show']
    )->name('businesses.service-enquiries.show');

    Route::patch(
        'businesses/{business}/service-enquiries/{enquiry}/status',
        [ServiceEnquiryController::class, 'updateStatus']
    )->name('businesses.service-enquiries.status');

    /*
|--------------------------------------------------------------------------
| Service Enquiry Follow-up Management
|--------------------------------------------------------------------------
*/

    Route::get(
        'businesses/{business}/service-follow-ups',
        [ServiceEnquiryFollowUpController::class, 'index']
    )->name('businesses.service-follow-ups.index');

    Route::post(
        'businesses/{business}/service-enquiries/{enquiry}/follow-ups',
        [ServiceEnquiryFollowUpController::class, 'store']
    )->name('businesses.service-enquiries.follow-ups.store');

    Route::patch(
        'businesses/{business}/service-enquiries/{enquiry}/follow-ups/{followUp}/complete',
        [ServiceEnquiryFollowUpController::class, 'complete']
    )->name('businesses.service-enquiries.follow-ups.complete');

    /*
|--------------------------------------------------------------------------
| Growya Service Appointments
|--------------------------------------------------------------------------
*/

    Route::get(
        'businesses/{business}/appointments',
        [AppointmentController::class, 'index']
    )->name('businesses.appointments.index');

    Route::get(
        'businesses/{business}/appointments/create',
        [AppointmentController::class, 'create']
    )->name('businesses.appointments.create');

    Route::post(
        'businesses/{business}/appointments',
        [AppointmentController::class, 'store']
    )->name('businesses.appointments.store');

    Route::get(
        'businesses/{business}/appointments/{appointment}',
        [AppointmentController::class, 'show']
    )->name('businesses.appointments.show');

    Route::patch(
        'businesses/{business}/appointments/{appointment}/status',
        [AppointmentController::class, 'updateStatus']
    )->name('businesses.appointments.status');

    /*
|--------------------------------------------------------------------------
| Business Gallery Management
|--------------------------------------------------------------------------
*/

    Route::get(
        'businesses/{business}/gallery',
        [BusinessGalleryController::class, 'index']
    )->name('businesses.gallery.index');

    Route::get(
        'businesses/{business}/gallery/create',
        [BusinessGalleryController::class, 'create']
    )->name('businesses.gallery.create');

    Route::post(
        'businesses/{business}/gallery',
        [BusinessGalleryController::class, 'store']
    )->name('businesses.gallery.store');

    Route::get(
        'businesses/{business}/gallery/{gallery}/edit',
        [BusinessGalleryController::class, 'edit']
    )->name('businesses.gallery.edit');

    Route::put(
        'businesses/{business}/gallery/{gallery}',
        [BusinessGalleryController::class, 'update']
    )->name('businesses.gallery.update');

    Route::delete(
        'businesses/{business}/gallery/{gallery}',
        [BusinessGalleryController::class, 'destroy']
    )->name('businesses.gallery.destroy');

    /*
|--------------------------------------------------------------------------
| Super Admin - Business Verification
|--------------------------------------------------------------------------
*/

    Route::get(
        'admin/business-verifications',
        [BusinessVerificationController::class, 'index']
    )->name('admin.business-verifications.index');

    Route::get(
        'admin/business-verifications/{business}',
        [BusinessVerificationController::class, 'show']
    )->name('admin.business-verifications.show');

    Route::patch(
        'admin/business-verifications/{business}/approve',
        [BusinessVerificationController::class, 'approve']
    )->name('admin.business-verifications.approve');

    Route::patch(
        'admin/business-verifications/{business}/reject',
        [BusinessVerificationController::class, 'reject']
    )->name('admin.business-verifications.reject');

    Route::patch(
        'admin/business-verifications/{business}/pending',
        [BusinessVerificationController::class, 'pending']
    )->name('admin.business-verifications.pending');

    Route::get('businesses/{business}/offers', [BusinessOfferController::class, 'index'])->name('businesses.offers.index');
    Route::get('businesses/{business}/offers/create', [BusinessOfferController::class, 'create'])->name('businesses.offers.create');
    Route::post('businesses/{business}/offers', [BusinessOfferController::class, 'store'])->name('businesses.offers.store');
    Route::get('businesses/{business}/offers/{offer}/edit', [BusinessOfferController::class, 'edit'])->name('businesses.offers.edit');
    Route::put('businesses/{business}/offers/{offer}', [BusinessOfferController::class, 'update'])->name('businesses.offers.update');
    Route::delete('businesses/{business}/offers/{offer}', [BusinessOfferController::class, 'destroy'])->name('businesses.offers.destroy');
});

/*
|--------------------------------------------------------------------------
| Public Business Showroom
|--------------------------------------------------------------------------
*/

Route::get(
    'b/{slug}',
    [PublicBusinessController::class, 'home']
)->name('public.business.home');

Route::get(
    'b/{slug}/products',
    [PublicBusinessController::class, 'products']
)->name('public.business.products');

Route::get(
    'b/{slug}/products/{productSlug}',
    [PublicBusinessController::class, 'product']
)->name('public.business.product');

Route::get(
    'b/{slug}/services',
    [PublicBusinessController::class, 'services']
)->name('public.business.services');

Route::get(
    'b/{slug}/services/{serviceSlug}',
    [PublicBusinessController::class, 'service']
)->name('public.business.service');

Route::get(
    'b/{slug}/gallery',
    [PublicBusinessController::class, 'gallery']
)->name('public.business.gallery');

Route::get(
    'b/{slug}/contact',
    [PublicBusinessController::class, 'contact']
)->name('public.business.contact');

/*
|--------------------------------------------------------------------------
| Public Service Enquiries
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get(
        's/{business}/{service}/enquiry',
        [PublicServiceEnquiryController::class, 'create']
    )->name('public.services.enquiries.create');

    Route::post(
        's/{business}/{service}/enquiry',
        [PublicServiceEnquiryController::class, 'store']
    )->middleware('throttle:5,1')->name('public.services.enquiries.store');
});

Route::get(
    'p/{business}/{product}',
    [PublicProductController::class, 'show']
)->name('public.products.show');

Route::middleware(['auth'])->group(function () {
    Route::livewire('invitations/{invitation}/accept', 'pages::teams.accept-invitation')
        ->name('invitations.accept');
});

require __DIR__.'/settings.php';

Route::middleware('auth')->group(function () {
    Route::get('businesses/{business}/catalog/{kind}', [TenantCatalogController::class, 'index'])->whereIn('kind', ['categories', 'attributes'])->name('businesses.catalog.index');
    Route::post('businesses/{business}/catalog/{kind}', [TenantCatalogController::class, 'store'])->whereIn('kind', ['categories', 'attributes'])->name('businesses.catalog.store');
    Route::put('businesses/{business}/catalog/{kind}/{item}', [TenantCatalogController::class, 'update'])->whereIn('kind', ['categories', 'attributes'])->whereNumber('item')->name('businesses.catalog.update');
    Route::delete('businesses/{business}/catalog/{kind}/{item}', [TenantCatalogController::class, 'destroy'])->whereIn('kind', ['categories', 'attributes'])->whereNumber('item')->name('businesses.catalog.destroy');
    Route::post('businesses/{business}/catalog-attributes/{attribute}/values', [TenantCatalogController::class, 'storeValue'])->name('businesses.catalog.values.store');
    Route::delete('businesses/{business}/catalog-attributes/{attribute}/values/{value}', [TenantCatalogController::class, 'destroyValue'])->name('businesses.catalog.values.destroy');
});

require __DIR__.'/extensions_web.php';

// Browser push uses session authentication + CSRF; API devices remain bearer-token based.
Route::get('firebase-messaging-sw.js', [GrowyaWebPushController::class, 'worker'])->name('growya.push.worker');
Route::middleware(['auth', 'throttle:20,1'])->group(function () {
    Route::get('my-notifications/push/config', [GrowyaWebPushController::class, 'config'])->name('growya.push.config');
    Route::post('my-notifications/push/register', [GrowyaWebPushController::class, 'store'])->name('growya.push.store');
    Route::post('my-notifications/push/disable', [GrowyaWebPushController::class, 'disable'])->name('growya.push.disable');
    Route::post('my-notifications/push/test', [GrowyaWebPushController::class, 'test'])->middleware('throttle:3,1')->name('growya.push.test');
    Route::get('my-notifications/{recipient}/visit', [GrowyaNotificationController::class, 'open'])->name('growya.notifications.visit');
});
