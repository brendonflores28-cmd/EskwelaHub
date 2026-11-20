<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faq_entries', function (Blueprint $table) {
            $table->string('question_tl')->nullable()->after('question');   // the same question in Tagalog (for suggestions)
            $table->text('answer_tl')->nullable()->after('answer');         // the answer in Tagalog
            $table->string('guide', 40)->nullable()->after('answer_tl');    // id of a step-by-step guide (config/guides.php)
        });
    }

    public function down(): void
    {
        Schema::table('faq_entries', function (Blueprint $table) {
            $table->dropColumn(['question_tl', 'answer_tl', 'guide']);
        });
    }
};
