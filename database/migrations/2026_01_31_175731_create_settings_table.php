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
         Schema::create('settings', function (Blueprint $table) {
            $table->id()->primary();
            $table->string('slug', 100)->unique();
            $table->string('title', 100);
            $table->boolean('json')->default(0);
            $table->text('setting_value');
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }

};
