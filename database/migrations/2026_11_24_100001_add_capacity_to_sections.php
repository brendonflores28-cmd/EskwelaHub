<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            // Maximum learners in the section. Empty means no limit is enforced.
            $table->unsignedSmallInteger('capacity')->nullable()->after('adviser_name');
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('capacity');
        });
    }
};
