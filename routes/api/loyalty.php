<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\Api\ReferralController as ApiReferralController;
use App\Http\Controllers\PartnerController;

/*
|--------------------------------------------------------------------------
| Loyalty, Referrals & Points Routes
|--------------------------------------------------------------------------
*/

// Public Referral Validation Routes
Route::prefix('v1/referrals')->group(function () {
    Route::post('validate', [ApiReferralController::class, 'validateReferral']);
    Route::get('available-tiers', [ReferralController::class, 'getAvailableTiers']);
});

// Public Points API Routes
Route::prefix('v1/points')->group(function () {
    Route::get('/user', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getUserPoints']);
    Route::get('/history', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getPointsHistory']);
    Route::get('/stats', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getPointsStats']);
    Route::get('/rewards/affordable', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getAffordableRewards']);
    Route::get('/rewards', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getAllRewards']);
    Route::post('/rewards/{id}/redeem', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'redeemReward']);
    Route::get('/redemptions', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getUserRedemptions']);
});

// Public partner businesses routes
Route::prefix('v1')->group(function () {
    Route::get('partners', [PartnerController::class, 'index']);
});

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Mobile Points API Routes (Protected)
    Route::prefix('v1/points')->group(function () {
        Route::get('/history', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getPointsHistory']);
        Route::get('/stats', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getPointsStats']);
        Route::get('/rewards/affordable', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getAffordableRewards']);
        Route::get('/rewards', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getAllRewards']);
        Route::post('/rewards/{id}/redeem', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'redeemReward']);
        Route::get('/redemptions', [\App\Http\Controllers\Api\Mobile\PointsController::class, 'getUserRedemptions']);
    });

    Route::prefix('v1')->group(function () {
        // Referral routes
        Route::get('referral/data', [ReferralController::class, 'getUserReferralData']);
        Route::post('referral/redeem/{reward}', [ReferralController::class, 'redeemReward']);
        Route::post('referral/process', [ReferralController::class, 'processReferral']);

        // How Found Us Referral Routes
        Route::get('referrals/my-referrals', [ApiReferralController::class, 'myReferrals']);
        Route::get('referrals/dashboard', [ReferralController::class, 'enhancedDashboard']);

        // Partner business routes
        Route::post('partners/offers/{offer}/redeem', [PartnerController::class, 'generateRedemptionCode']);
        Route::post('partners/redemptions/{redemption}/use', [PartnerController::class, 'useRedemption']);
        Route::get('partners/redemptions', [PartnerController::class, 'getUserRedemptions']);

        // Partner Offers Routes (authenticated access)
        Route::get('partner-offers/available', [PartnerController::class, 'getAvailableOffers']);
        Route::post('partner-offers/{offer}/redeem', [PartnerController::class, 'redeemOffer']);
    });

    // User Loyalty Routes (Protected)
    Route::prefix('v1/loyalty')->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\Api\LoyaltyController::class, 'dashboard']);
        Route::get('points/history', [\App\Http\Controllers\Api\LoyaltyController::class, 'pointsHistory']);
        Route::get('rewards/available', [\App\Http\Controllers\Api\LoyaltyController::class, 'availableRewards']);
        Route::post('rewards/{loyaltyReward}/redeem', [\App\Http\Controllers\Api\LoyaltyController::class, 'redeemReward']);
        Route::get('redemptions', [\App\Http\Controllers\Api\LoyaltyController::class, 'myRedemptions']);
        Route::get('redemptions/{redemptionCode}/check', [\App\Http\Controllers\Api\LoyaltyController::class, 'checkRedemption']);
    });

    // Admin only routes
    Route::prefix('v1')->middleware(['role:admin'])->group(function () {
        // Points Settings (Admin only)
        Route::prefix('points')->group(function () {
            Route::get('settings', [\App\Http\Controllers\Api\PointsSettingsController::class, 'getSettings']);
            Route::put('settings', [\App\Http\Controllers\Api\PointsSettingsController::class, 'updateSettings']);
            Route::post('settings/reset', [\App\Http\Controllers\Api\PointsSettingsController::class, 'resetSettings']);
            Route::get('user', [\App\Http\Controllers\Api\PointsSettingsController::class, 'getUserPoints']);
            Route::post('users', [\App\Http\Controllers\Api\PointsSettingsController::class, 'getUsersPoints']);
        });

        // Admin Referral Management
        Route::get('admin/referral-codes', [ReferralController::class, 'adminGetCodes']);
        Route::get('admin/referral-rewards', [ReferralController::class, 'adminGetRewards']);
        Route::post('admin/referral-rewards', [ReferralController::class, 'adminCreateReward']);
        Route::put('admin/referral-rewards/{reward}', [ReferralController::class, 'adminUpdateReward']);
        Route::delete('admin/referral-rewards/{reward}', [ReferralController::class, 'adminDeleteReward']);
        Route::get('admin/referrals', [ReferralController::class, 'adminGetReferrals']);

        // How Found Us Admin Routes
        Route::get('admin/referrals/top-referrers', [ApiReferralController::class, 'topReferrers']);
        Route::get('admin/referrals/source-statistics', [ApiReferralController::class, 'sourceStatistics']);

        // Admin Partner Management
        Route::get('admin/partners', [PartnerController::class, 'adminGetPartners']);
        Route::post('admin/partners', [PartnerController::class, 'adminCreatePartner']);
        Route::put('admin/partners/{partner}', [PartnerController::class, 'adminUpdatePartner']);
        Route::delete('admin/partners/{partner}', [PartnerController::class, 'adminDeletePartner']);
        Route::get('admin/partner-offers', [PartnerController::class, 'adminGetOffers']);
        Route::post('admin/partner-offers', [PartnerController::class, 'adminCreateOffer']);
        Route::put('admin/partner-offers/{offer}', [PartnerController::class, 'adminUpdateOffer']);
        Route::delete('admin/partner-offers/{offer}', [PartnerController::class, 'adminDeleteOffer']);
        Route::get('admin/partner-redemptions', [PartnerController::class, 'adminGetRedemptions']);
    });

    // Referral Management (Admin & Trainer)
    Route::prefix('v1')->middleware(['role:admin,trainer'])->group(function () {
        Route::get('admin/referrals/phone-based', [\App\Http\Controllers\UserController::class, 'getPhoneBasedReferrals']);
    });
});

// Admin Loyalty Management (Protected)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin')->group(function () {
    // Loyalty Rewards CRUD
    Route::apiResource('loyalty-rewards', \App\Http\Controllers\Api\LoyaltyRewardController::class);
    Route::post('loyalty-rewards/{loyaltyReward}/toggle-status', [\App\Http\Controllers\Api\LoyaltyRewardController::class, 'toggleStatus']);
    Route::get('loyalty-rewards/{loyaltyReward}/redemptions', [\App\Http\Controllers\Api\LoyaltyRewardController::class, 'redemptions']);

    // Loyalty Redemptions Management
    Route::get('loyalty/redemptions', [\App\Http\Controllers\Api\LoyaltyController::class, 'adminGetRedemptions']);
    Route::get('loyalty-redemptions', [\App\Http\Controllers\Api\LoyaltyController::class, 'adminGetRedemptions']);

    // Loyalty Statistics
    Route::get('loyalty/stats', [\App\Http\Controllers\Api\LoyaltyController::class, 'stats']);

    // Referral Reward Tiers CRUD
    Route::apiResource('referral-reward-tiers', \App\Http\Controllers\Api\ReferralRewardTierController::class);
    Route::post('referral-reward-tiers/{referralRewardTier}/toggle-status', [\App\Http\Controllers\Api\ReferralRewardTierController::class, 'toggleStatus']);

    // Enhanced Referral Management
    Route::get('referral-stats', [ReferralController::class, 'adminGetStats']);

    // Points Rewards Management
    Route::apiResource('points/rewards', \App\Http\Controllers\Api\PointsRewardsController::class);
});
