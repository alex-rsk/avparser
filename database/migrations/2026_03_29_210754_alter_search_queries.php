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
            $table->unsignedTinyInteger('is_enabled')->default(1);
            $table->string('category_url', 4096)->after('query_text')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropColumns('search_queries', 'is_enabled');
        Schema::table('search_queries', function (Blueprint $table) {
            $table->string('category_url', 4096)->after('query_text')->change();
        });
    }
};
