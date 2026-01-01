<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->nullableMorphs('favoritable');
            $table->index(['favoritable_id', 'favoritable_type']);
        });

        Artisan::call('favorites:backfill');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('favorites', 'post_id')) {
            Schema::table('favorites', function (Blueprint $table) {
                $table->unsignedBigInteger('post_id')->nullable()->after('id');
            });
        }

        Artisan::call('favorites:rollback');

        Schema::table('favorites', function (Blueprint $table) {
            $table->dropIndex(['favoritable_id', 'favoritable_type']);
            $table->dropMorphs('favoritable');
        });
    }
};
