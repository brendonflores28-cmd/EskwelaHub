<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('original_name');
            $table->string('disk_path');                                   // inside storage/app/private
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->string('engine', 30);                                  // browser | ocr_space
            $table->timestamps();
        });

        Schema::create('ocr_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_upload_id')->constrained()->cascadeOnDelete();
            $table->longText('raw_text')->nullable();                      // text found by the OCR engine
            $table->json('parsed')->nullable();                            // fields guessed from the text
            $table->string('status', 20)->default('pending')->index();     // pending | verified | rejected
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocr_drafts');
        Schema::dropIfExists('scan_uploads');
    }
};
