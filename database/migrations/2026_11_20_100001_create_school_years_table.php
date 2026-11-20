<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_years', function (Blueprint $table) {
            $table->id();
            $table->string('label', 9)->unique();                  // "1999-2000"
            $table->unsignedSmallInteger('start_year')->unique();  // 1999
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('grade_level');            // 7-12
            $table->string('name', 60);
            $table->string('adviser_name')->nullable();
            $table->timestamps();

            $table->unique(['school_year_id', 'grade_level', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
        Schema::dropIfExists('school_years');
    }
};
