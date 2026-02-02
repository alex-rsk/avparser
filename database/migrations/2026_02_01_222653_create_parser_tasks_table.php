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
        Schema::create('parser_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title', 1024);
            $table->unsignedInteger('search_query_id');                
            $table->unsignedTinyInteger('priority')->default(1);
            $table->unsignedBigInteger('process_pid')->nullable();
            $table->timestamps();

            $table->foreign('search_query_id')->references('id')->on('search_queries')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parser_tasks');
    }
};
