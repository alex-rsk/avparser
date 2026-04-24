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
        if (!Schema::hasColumns('search_queries', ['order_id'])) {
            Schema::table('search_queries', function (Blueprint $table){
                $table->unsignedBigInteger('order_id')->after('id')->nullable();
                $table->index('order_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumns('search_queries', ['order_id'])) {
            Schema::dropColumns('search_queries', ['order_id']);
        }
    }
};
