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
            $table->string('title')->after('id');
            $table->string('mode', 20)->after('title')->default('url');
            $table->string('query_text', 1024)->nullable()->after('priority')->change();
            $table->string('category_url', 4096)->after('query_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_queries', function (Blueprint $table) {
            $table->string('query_text', 1024)->after('priority')->change();            
        });
        if (Schema::hasColumns('search_queries', ['category_url'])) {
            Schema::dropColumns('search_queries', ['category_url', 'title', 'mode']);
        }
    }
};
