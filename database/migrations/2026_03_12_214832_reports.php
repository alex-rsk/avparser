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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('downloaded')->default(0);
            $table->string('from_date')->nullable();
            $table->string('to_date')->nullable();
            $table->unsignedInteger('search_query_id')->nullable();
            $table->string('filename', 512);
            $table->unsignedInteger('owner_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
