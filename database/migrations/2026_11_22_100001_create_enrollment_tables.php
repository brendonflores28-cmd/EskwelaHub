<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 30)->nullable()->unique();      // ENR-2026-000123
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // who applied
            $table->foreignId('school_year_id')->constrained()->restrictOnDelete();
            $table->string('type', 20);                                    // grade7 | transferee
            $table->unsignedTinyInteger('grade_level');                    // 7 for new learners, 7-12 for transferees
            $table->string('relationship', 20)->default('parent');         // applicant is: self, parent, guardian

            // Learner data, copied into a student record when approved.
            $table->string('lrn', 12)->nullable();
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('suffix', 10)->nullable();
            $table->char('sex', 1);
            $table->date('birthdate');
            $table->string('birthplace')->nullable();
            $table->string('address');
            $table->string('guardian_name');
            $table->string('guardian_contact', 30);
            $table->string('previous_school')->nullable();                 // required for transferees

            $table->string('status', 20)->index();
            $table->text('decision_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete(); // set on approval
            $table->timestamps();
        });

        Schema::create('enrollment_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_application_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 40);                                    // psa_birth_certificate, report_card, ...
            $table->string('original_name');                               // shown to staff, never used as a path
            $table->string('disk_path');                                   // inside storage/app/private
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->char('sha256', 64);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_documents');
        Schema::dropIfExists('enrollment_applications');
    }
};
