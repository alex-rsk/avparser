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
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('search_query_id');
            $table->enum('status', ['new', 'visited', 'error', 'other'])->default('new');
            $table->string('title', 255);
            $table->string('vendor', 255)->nullable();
            $table->double('price')->default(0)->nullable();
            $table->double('rating')->nullable();
            $table->unsignedBigInteger('avito_id')->nullable();
            $table->dateTime('placed_at')->nullable();
            $table->string('comment', 255)->nullable();
            $table->string('url', 2048);
            $table->string('clean_url', 2048);
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('last_visited_at')->nullable();            

            $table->foreign('search_query_id')
                  ->references('id')
                  ->on('search_queries')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
