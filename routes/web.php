<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Member\AffiliateController as MemberAffiliateController;
use App\Http\Controllers\Member\ImportAffiliateController;
use App\Http\Controllers\Member\KeywordGeneratorController;
use App\Http\Controllers\Member\CouponController as MemberCouponController;
use App\Http\Controllers\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\Member\PostController as MemberPostController;
use App\Http\Controllers\Member\StoreController as MemberStoreController;
use App\Http\Controllers\EditorImageController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\StoreController as AdminStoreController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\AffiliateOrderController as AdminAffiliateOrderController;
use App\Http\Controllers\Admin\AffiliatePayoutController as AdminAffiliatePayoutController;
use App\Http\Controllers\ReferralController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [HomeController::class, 'search'])->middleware('noindex')->name('search');

Route::get('/coupons', [CouponController::class, 'index'])->name('coupons.index');
Route::get('/coupons/{slug}', [CouponController::class, 'show'])->name('coupons.show');
Route::post('/coupons/{slug}/reveal', [CouponController::class, 'reveal'])->middleware('noindex')->name('coupons.reveal');
Route::get('/coupons/{slug}/go', [CouponController::class, 'go'])->middleware('noindex')->name('coupons.go');

Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
Route::get('/stores/{slug}', [StoreController::class, 'show'])->name('stores.show');

Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');

Route::get('/about-us', [PageController::class, 'about'])->name('pages.about');
Route::get('/contact-us', [PageController::class, 'contact'])->name('pages.contact');
Route::post('/contact-us', [PageController::class, 'contactSubmit'])->name('pages.contact.submit');
Route::get('/privacy-policy', [PageController::class, 'privacy'])->name('pages.privacy');
Route::get('/terms-of-service', [PageController::class, 'terms'])->name('pages.terms');
Route::get('/cookie-policy', [PageController::class, 'cookies'])->name('pages.cookies');
Route::get('/disclaimer', [PageController::class, 'disclaimer'])->name('pages.disclaimer');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::middleware(['guest', 'noindex'])->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'noindex'])->post('/editor/upload-image', [EditorImageController::class, 'store'])
    ->name('editor.upload-image');

Route::middleware(['auth', 'noindex'])->prefix('dashboard')->name('member.')->group(function () {
    Route::get('/', [MemberDashboardController::class, 'index'])->name('dashboard');
    Route::get('import-affiliate', [ImportAffiliateController::class, 'create'])->name('import-affiliate.create');
    Route::post('import-affiliate/preview', [ImportAffiliateController::class, 'preview'])->name('import-affiliate.preview');
    Route::post('import-affiliate', [ImportAffiliateController::class, 'store'])->name('import-affiliate.store');
    Route::get('keywords', [KeywordGeneratorController::class, 'create'])->name('keywords.create');
    Route::get('keywords/load', [KeywordGeneratorController::class, 'load'])->name('keywords.load');
    Route::post('keywords', [KeywordGeneratorController::class, 'generate'])->name('keywords.generate');
    Route::resource('stores', MemberStoreController::class)->except(['show']);
    Route::resource('coupons', MemberCouponController::class)->except(['show']);
    Route::resource('posts', MemberPostController::class)->except(['show']);

    Route::middleware('affiliate.enabled')->group(function () {
        Route::get('affiliate', [MemberAffiliateController::class, 'index'])->name('affiliate.index');
        Route::get('affiliate/orders', [MemberAffiliateController::class, 'orders'])->name('affiliate.orders');
        Route::get('affiliate/payouts', [MemberAffiliateController::class, 'payouts'])->name('affiliate.payouts');
        Route::post('affiliate/payouts', [MemberAffiliateController::class, 'storePayout'])->name('affiliate.payouts.store');
    });
});

Route::middleware(['auth', 'admin', 'noindex'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('coupons', AdminCouponController::class)->except(['show']);
    Route::resource('stores', AdminStoreController::class)->except(['show']);
    Route::resource('categories', AdminCategoryController::class)->except(['show']);
    Route::resource('posts', AdminPostController::class)->except(['show']);
    Route::get('themes', [ThemeController::class, 'index'])->name('themes.index');
    Route::put('themes', [ThemeController::class, 'update'])->name('themes.update');

    Route::middleware('affiliate.enabled')->group(function () {
        Route::get('affiliate/orders', [AdminAffiliateOrderController::class, 'index'])->name('affiliate.orders.index');
        Route::get('affiliate/orders/create', [AdminAffiliateOrderController::class, 'create'])->name('affiliate.orders.create');
        Route::post('affiliate/orders', [AdminAffiliateOrderController::class, 'store'])->name('affiliate.orders.store');
        Route::get('affiliate/orders/{order}/edit', [AdminAffiliateOrderController::class, 'edit'])->name('affiliate.orders.edit');
        Route::put('affiliate/orders/{order}', [AdminAffiliateOrderController::class, 'update'])->name('affiliate.orders.update');
        Route::get('affiliate/payouts', [AdminAffiliatePayoutController::class, 'index'])->name('affiliate.payouts.index');
        Route::get('affiliate/payouts/{payout}/edit', [AdminAffiliatePayoutController::class, 'edit'])->name('affiliate.payouts.edit');
        Route::put('affiliate/payouts/{payout}', [AdminAffiliatePayoutController::class, 'update'])->name('affiliate.payouts.update');
    });
});

Route::middleware('affiliate.enabled')->group(function () {
    Route::get('/ref/{code}', [ReferralController::class, 'landing'])->name('referral.landing');
});
