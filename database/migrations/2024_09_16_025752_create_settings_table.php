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
            $table->id(); // Primary key
            $table->uuid('user_id')->unique(); // Đảm bảo mỗi user chỉ có 1 setting
            $table->string('language')->nullable();
            $table->string('color_system')->nullable();
            $table->string('screen_mode')->default('default'); // 'default' hoặc 'full'
            $table->timestamps();
        
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
