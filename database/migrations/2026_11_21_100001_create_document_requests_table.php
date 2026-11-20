<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no', 30)->nullable()->unique();   // REQ-2026-000123, set right after insert
            $table->foreignId('user_id')->constrained()->restrictOnDelete();       // who asked
            $table->foreignId('student_id')->constrained()->restrictOnDelete();    // whose record
            $table->string('document_type', 40);
            $table->string('purpose');
            $table->unsignedTinyInteger('copies')->default(1);
            $table->string('status', 20)->index();
            $table->text('remarks')->nullable();                      // staff note shown to the requester
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->string('verification_token', 64)->unique();       // used in the QR code
            $table->timestamps();
        });

        Schema::create('request_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_request_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete(); // null = system
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_request_id')->constrained()->cascadeOnDelete();
            $table->string('disk_path');                              // inside storage/app/private
            $table->char('sha256', 64);
            $table->unsignedInteger('size');
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
        Schema::dropIfExists('request_status_histories');
        Schema::dropIfExists('document_requests');
    }
};
