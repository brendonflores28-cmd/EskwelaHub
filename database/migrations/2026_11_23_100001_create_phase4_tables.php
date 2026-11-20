<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pairs of student records that may be the same person. student_a_id is always the smaller id.
        Schema::create('duplicate_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_a_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('student_b_id')->constrained('students')->cascadeOnDelete();
            $table->decimal('score', 4, 3);                                // name similarity, 0 to 1
            $table->string('reason');
            $table->string('status', 20)->default('open')->index();        // open | dismissed | merged
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['student_a_id', 'student_b_id']);
        });

        // Client satisfaction survey, one per user per transaction.
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_request_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_application_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating_overall');                 // 1 to 5
            $table->unsignedTinyInteger('rating_speed');
            $table->unsignedTinyInteger('rating_staff');
            $table->text('comment')->nullable();
            $table->string('sentiment', 10)->nullable()->index();          // positive | neutral | negative (of the comment)
            $table->decimal('sentiment_score', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'document_request_id']);
            $table->unique(['user_id', 'enrollment_application_id']);
        });

        Schema::create('faq_entries', function (Blueprint $table) {
            $table->id();
            $table->string('category', 40);
            $table->string('question');
            $table->text('keywords');                                      // extra words people use, English and Tagalog
            $table->text('answer');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('chat_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('question', 300);
            $table->foreignId('faq_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('matched')->default(false);                    // false = the bot did not know: review these
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_logs');
        Schema::dropIfExists('faq_entries');
        Schema::dropIfExists('surveys');
        Schema::dropIfExists('duplicate_flags');
    }
};
