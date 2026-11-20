<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Senior high school (Grades 11 and 12): strand of the learner and of the class section. */
    public function up(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->string('strand', 12)->nullable()->after('grade_level')->index();
        });
        Schema::table('sections', function (Blueprint $table) {
            $table->string('strand', 12)->nullable()->after('grade_level');
        });
        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->string('strand', 12)->nullable()->after('grade_level');
        });
    }

    public function down(): void
    {
        Schema::table('student_enrollments', fn (Blueprint $t) => $t->dropColumn('strand'));
        Schema::table('sections', fn (Blueprint $t) => $t->dropColumn('strand'));
        Schema::table('enrollment_applications', fn (Blueprint $t) => $t->dropColumn('strand'));
    }
};
