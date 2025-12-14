<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sessions')) {
            throw new RuntimeException(
                'The sessions table does not exist. Please run: php artisan session:table && php artisan migrate'
            );
        }

        Schema::table('sessions', function (Blueprint $table) {
            // Make payload nullable for metadata-only records with Redis driver
            if (Schema::hasColumn('sessions', 'payload')) {
                $table->text('payload')->nullable()->change();
            }

            // Add composite index for efficient user session queries
            $table->index(['user_id', 'last_activity'], 'session_manager_user_last_activity');
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex('session_manager_user_last_activity');
            if (Schema::hasColumn('sessions', 'payload')) {
                $table->text('payload')->nullable(false)->change();
            }
        });
    }
};
