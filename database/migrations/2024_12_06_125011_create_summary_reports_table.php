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
        Schema::create('summary_reports', function (Blueprint $table) {
            $table->id('summary_report_id'); // Primary key
            $table->unsignedBigInteger('project_id')->nullable()->index();// Không xóa khi project bị xóa
            $table->string('project_name')->nullable(); // Lưu tên project ngay cả khi bị xóa
            $table->text('project_description')->nullable(); // Mô tả dự án ngay cả khi bị xóa
            $table->text('name'); // Tên báo cáo
            $table->uuid('reported_by_user_id'); // Người tạo báo cáo
            $table->date('report_date'); // Ngày báo cáo
            $table->text('summary'); // Tóm tắt báo cáo
            $table->text('completed_tasks')->nullable(); // Các công việc đã hoàn thành
            $table->text('upcoming_tasks')->nullable(); // Các công việc sắp tới
            $table->text('project_issues')->nullable(); // Các vấn đề của dự án
            $table->string('zip_name')->nullable(); // Tên file ZIP
            $table->string('zip_file_path')->nullable(); // Đường dẫn file ZIP
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes(); // Soft delete để lưu trữ lâu dài

            $table->foreign('reported_by_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('summary_reports');
    }
};
