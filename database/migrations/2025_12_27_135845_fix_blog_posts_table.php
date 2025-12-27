<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration {
    public function up(): void
    {
        try {
            Schema::table('blog_posts', function (Blueprint $table) {
                // Add missing columns if they don't exist
                if (!Schema::hasColumn('blog_posts', 'meta_title')) {
                    $table->string('meta_title')->nullable();
                }
                if (!Schema::hasColumn('blog_posts', 'meta_description')) {
                    $table->text('meta_description')->nullable();
                }
                if (!Schema::hasColumn('blog_posts', 'tags')) {
                    $table->json('tags')->nullable();
                }
                if (!Schema::hasColumn('blog_posts', 'read_time')) {
                    $table->integer('read_time')->nullable();
                }
                if (!Schema::hasColumn('blog_posts', 'views_count')) {
                    $table->unsignedInteger('views_count')->default(0);
                }
            });
        } catch (\Exception $e) {
            // Log warning if we can't modify table due to privilege issues
            Log::warning('Could not add missing columns to blog_posts: ' . $e->getMessage());
            Log::info('Please run the following SQL as the database owner to add missing columns:');
            Log::info("ALTER TABLE blog_posts ADD COLUMN meta_title VARCHAR(191) NULL;");
            Log::info("ALTER TABLE blog_posts ADD COLUMN meta_description TEXT NULL;");
            Log::info("ALTER TABLE blog_posts ADD COLUMN tags JSON NULL;");
            Log::info("ALTER TABLE blog_posts ADD COLUMN read_time INTEGER NULL;");
        }
    }

    public function down(): void
    {
        try {
            Schema::table('blog_posts', function (Blueprint $table) {
                $columns = ['meta_title', 'meta_description', 'tags', 'read_time', 'views_count'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('blog_posts', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        } catch (\Exception $e) {
            Log::warning('Could not drop columns from blog_posts: ' . $e->getMessage());
        }
    }
};
