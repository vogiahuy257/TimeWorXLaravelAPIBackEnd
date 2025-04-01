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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id(); // Primary key (bigint, auto-increment)
            $table->uuid('user_id')->index(); // Foreign key to 'users' table
            $table->enum('notification_type', ['info', 'warning', 'error', 'success']); // Enum for notification type
            $table->text('message'); // Notification message
            $table->timestamp('notification_date')->useCurrent(); // Auto-set current timestamp
            $table->boolean('read_status')->default(false)->index(); // Read status (0 or 1)
            $table->text('link')->nullable(); // Link for the notification
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
