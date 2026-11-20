<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** A question asked to the chatbot. Unmatched ones show staff what the FAQ is missing. */
#[Fillable(['user_id', 'question', 'faq_entry_id', 'matched'])]
class ChatLog extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['matched' => 'boolean'];
    }
}
