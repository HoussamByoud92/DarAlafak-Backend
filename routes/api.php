<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    BookController,
    CategoryController,
    AuthorController,
    PublisherController,
    SerieController,
    PhysicalFormatController,
    KeywordController,
    OrderController,
    CartController,
    ContactController,
    BlogController,
    BlogCategoryController,
    BlogTagController,
    CatalogueController,
    WishlistController,
    ReviewController
};

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Public book routes
Route::get('/books', [BookController::class, 'index']);
Route::get('/books/featured', [BookController::class, 'featured']);
Route::get('/books/recent', [BookController::class, 'recent']);
Route::get('/books/{slug}', [BookController::class, 'show']);

// Public category routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);

// Public author routes
Route::get('/authors', [AuthorController::class, 'index']);
Route::get('/authors/{slug}', [AuthorController::class, 'show']);
Route::get('/authors/{slug}/books', [AuthorController::class, 'books']);

// Public publisher routes
Route::get('/publishers', [PublisherController::class, 'index']);
Route::get('/publishers/{slug}', [PublisherController::class, 'show']);
Route::get('/publishers/{slug}/books', [PublisherController::class, 'books']);

// Public series routes
Route::get('/series', [SerieController::class, 'index']);
Route::get('/series/{slug}', [SerieController::class, 'show']);
Route::get('/series/{slug}/books', [SerieController::class, 'books']);

// Public physical formats routes
Route::get('/physical-formats', [PhysicalFormatController::class, 'index']);
Route::get('/physical-formats/{slug}', [PhysicalFormatController::class, 'show']);
Route::get('/physical-formats/{slug}/books', [PhysicalFormatController::class, 'books']);

// Public keywords routes
Route::get('/keywords', [KeywordController::class, 'index']);
Route::get('/keywords/popular', [KeywordController::class, 'popular']);
Route::get('/keywords/{slug}', [KeywordController::class, 'show']);
Route::get('/keywords/{slug}/books', [KeywordController::class, 'books']);

// Public blog routes
Route::get('/blog', [BlogController::class, 'index']);
Route::get('/blog/categories', [BlogCategoryController::class, 'index']);
Route::get('/blog/tags', [BlogTagController::class, 'index']);
Route::get('/blog/tags/popular', [BlogTagController::class, 'popular']);
Route::get('/blog/featured', [BlogController::class, 'featured']);
Route::get('/blog/recent', [BlogController::class, 'recent']);
Route::get('/blog/{slug}', [BlogController::class, 'show']);
Route::get('/blog/{post}/related', [BlogController::class, 'related']);

// Alias routes for frontend compatibility
Route::get('/blog-categories', [BlogCategoryController::class, 'index']);
Route::get('/blog-tags', [BlogTagController::class, 'index']);

// Public reviews routes
Route::get('/books/{book}/reviews', [ReviewController::class, 'index']);
Route::post('/books/{book}/reviews', [ReviewController::class, 'store']);

// Contact
Route::post('/contact', [ContactController::class, 'store']);

// Newsletter
Route::post('/newsletter/subscribe', [ContactController::class, 'subscribe']);
Route::post('/newsletter/unsubscribe', [ContactController::class, 'unsubscribe']);

// Public catalogues
Route::get('/catalogues', [CatalogueController::class, 'index']);
Route::get('/catalogues/popular', [CatalogueController::class, 'popular']);
Route::get('/catalogues/{catalogue}/download', [CatalogueController::class, 'download']);
Route::get('/catalogues/{slug}', [CatalogueController::class, 'show']);


// Orders (public - for guest checkout)
Route::post('/orders', [OrderController::class, 'store']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth user
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // User profile routes
    Route::get('/user/orders', [OrderController::class, 'userOrders']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);

    // User orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::put('/orders/{order}/cancel', [OrderController::class, 'cancel']);

    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{item}', [CartController::class, 'update']);
    Route::delete('/cart/{item}', [CartController::class, 'destroy']);
    Route::delete('/cart', [CartController::class, 'clear']);

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::delete('/wishlist/{book}', [WishlistController::class, 'destroy']);
    Route::delete('/wishlist', [WishlistController::class, 'clear']);
    Route::get('/wishlist/{book}/check', [WishlistController::class, 'check']);

    // Reviews (authenticated)
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

    // Admin routes
    Route::middleware(['role:admin|staff'])->group(function () {
        // Books management
        Route::apiResource('admin/books', BookController::class);

        // Categories management
        Route::apiResource('admin/categories', CategoryController::class);

        // Authors management
        Route::apiResource('admin/authors', AuthorController::class);

        // Publishers management
        Route::apiResource('admin/publishers', PublisherController::class);

        // Series management
        Route::apiResource('admin/series', SerieController::class);

        // Physical formats management
        Route::apiResource('admin/physical-formats', PhysicalFormatController::class);

        // Keywords management
        Route::apiResource('admin/keywords', KeywordController::class);

        // Orders management
        Route::get('admin/orders', [OrderController::class, 'index']);
        Route::get('admin/orders/statistics', [OrderController::class, 'statistics']);
        Route::get('admin/orders/{order}', [OrderController::class, 'show']);
        Route::put('admin/orders/{order}/status', [OrderController::class, 'updateStatus']);

        // Blog management
        Route::apiResource('admin/blog', BlogController::class);
        Route::apiResource('admin/blog-categories', BlogCategoryController::class);
        Route::apiResource('admin/blog-tags', BlogTagController::class);

        // Catalogues management
        Route::apiResource('admin/catalogues', CatalogueController::class);
        Route::get('admin/catalogues/statistics', [CatalogueController::class, 'statistics']);

        // Contact messages
        Route::get('admin/export/messages', [ContactController::class, 'export']);
        Route::get('admin/messages', [ContactController::class, 'index']);
        Route::get('admin/messages/{message}', [ContactController::class, 'show']);
        Route::put('admin/messages/{message}', [ContactController::class, 'update']);
        Route::put('admin/messages/{message}/read', [ContactController::class, 'markAsRead']);
        Route::delete('admin/messages/{message}', [ContactController::class, 'destroy']);

        // Newsletter subscribers
        Route::get('admin/newsletter/subscribers', [ContactController::class, 'newsletterSubscribers']);

        // Reviews management
        Route::get('admin/reviews/pending', [ReviewController::class, 'pending']);
        Route::put('admin/reviews/{review}/approve', [ReviewController::class, 'approve']);
        Route::put('admin/reviews/{review}/unapprove', [ReviewController::class, 'unapprove']);
        Route::delete('admin/reviews/{review}', [ReviewController::class, 'destroy']);
    });
});
