<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('lrn', 12)->nullable()->unique();       // old records (before about 2012) may have no LRN
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('suffix', 10)->nullable();
            $table->char('sex', 1);                                // M or F
            $table->date('birthdate');
            $table->string('birthplace')->nullable();
            $table->string('status', 20)->default('enrolled');
            $table->boolean('is_draft')->default(false);           // OCR drafts waiting for staff verification (Phase 3)
            $table->string('name_key')->index();                   // normalized, see App\Support\NameIndex
            $table->string('name_metaphone')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['birthdate']);
            $table->index(['last_name', 'first_name']);
        });

        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('grade_level');            // 7-12 (old 1st-4th year stored as 7-10)
            $table->string('curriculum', 10)->default('k12');
            $table->string('status', 20)->default('enrolled');
            $table->decimal('general_average', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'school_year_id']);
        });

        Schema::create('graduations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained()->restrictOnDelete();
            $table->date('date_graduated')->nullable();
            $table->string('honors')->nullable();
            $table->string('diploma_no')->nullable()->unique();
            $table->decimal('general_average', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graduations');
        Schema::dropIfExists('student_enrollments');
        Schema::dropIfExists('students');
    }
};
