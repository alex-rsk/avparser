<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('search_queries', function (Blueprint $table) {
            $table->time('launch_time')->after('category_url')->default('20:00:00')->nullable();
            $table->unsignedInteger('launch_interval')->after('launch_time')->default(24*60*60);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropColumns('search_queries', ['launch_time', 'launch_interval']);
    }
};
