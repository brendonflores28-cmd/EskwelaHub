<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One question and answer of the FAQ chatbot. `keywords` holds extra words
 * people use (English and Tagalog) so the bot can match them.
 *
 * @property int $id
 * @property string $category
 * @property string $question
 * @property string $keywords
 * @property string $answer
 * @property bool $is_active
 */
#[Fillable(['category', 'question', 'keywords', 'answer', 'is_active', 'sort'])]
class FaqEntry extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
