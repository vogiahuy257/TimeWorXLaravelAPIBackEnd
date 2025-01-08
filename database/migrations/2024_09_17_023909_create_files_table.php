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
        Schema::create('files', function (Blueprint $table) {
            $table->id('file_id'); // Primary key
            $table->string('name', 255); // File name
            $table->uuid('uploaded_by'); // Foreign key to 'users' table
            $table->timestamps(); // Uploaded at
            $table->foreignId('project_id')->constrained('projects', 'project_id')->onDelete('cascade'); // Foreign key to 'projects' table
            $table->string('type', 255); 
            $table->string('path', 255); // File path
            
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
            $table->softDeletes();
        });

        // liên kế 1 nhiều với report
        Schema::create('report_file', function (Blueprint $table) {
            $table->id(); // Khóa chính
            $table->foreignId('report_id')->constrained('reports', 'report_id')->onDelete('cascade'); // Khóa ngoại liên kết với bảng 'reports'
            $table->foreignId('file_id')->constrained('files', 'file_id')->onDelete('cascade'); // Khóa ngoại liên kết với bảng 'files'
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
        Schema::dropIfExists('report_file');
    }
};
