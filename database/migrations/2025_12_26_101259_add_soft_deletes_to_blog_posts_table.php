<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if deleted_at column already exists
        if (Schema::hasColumn('blog_posts', 'deleted_at')) {
            return;
        }

        try {
            Schema::table('blog_posts', function (Blueprint $table) {
                $table->softDeletes();
            });
        } catch (\Exception $e) {
            // Log warning if we can't modify table due to privilege issues
            Log::warning('Could not add soft deletes to blog_posts: ' . $e->getMessage());
            Log::info('The SoftDeletes trait will still work if you manually add the deleted_at column to the blog_posts table.');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('blog_posts', 'deleted_at')) {
            return;
        }

        try {
            Schema::table('blog_posts', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        } catch (\Exception $e) {
            Log::warning('Could not drop soft deletes from blog_posts: ' . $e->getMessage());
        }
    }
};
