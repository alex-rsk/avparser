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
         Schema::create('search_queries', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->string('query_text', 1024);
            $table->integer('total_pages')->nullable();
            $table->integer('last_seen_page')->nullable();
            $table->text('last_error')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('observed_at')->nullable();

            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};
