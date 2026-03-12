<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('uploaded_files', function (Blueprint $table) {
            $table->id();
            $table->string('original_name');
            $table->string('stored_name')->unique();
            $table->string('file_path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('uploaded_files');
    }
};
