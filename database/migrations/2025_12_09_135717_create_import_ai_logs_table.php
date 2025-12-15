<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('import_ai_logs', function (Blueprint $table) {
            $table->id();
            $table->string('job_type'); // 'gemini_file_processing', 'simple_gemini', etc.
            $table->string('job_class')->nullable(); // Job class name
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('file_path')->nullable(); // 處理的檔案路徑
            $table->string('gemini_file_uri')->nullable(); // Gemini File API URI
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // 額外資料如檔案大小、API呼叫次數等
            $table->text('result_summary')->nullable(); // 處理結果摘要
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('import_ai_logs');
    }
};
