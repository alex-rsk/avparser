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
        if (!Schema::hasTable('profiles')) {
            Schema::create('profiles', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('search_query_id');
                $table->string('user_agent', 1024);
                $table->string('proxy_nullable', 1024);
                $table->timestamps();

                $table->foreign('search_query_id')->references('id')->on('search_queries')->onDelete('CASCADE')->onUpdate('CASCADE');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {     
        Schema::dropIfExists('profiles');
    }
};
