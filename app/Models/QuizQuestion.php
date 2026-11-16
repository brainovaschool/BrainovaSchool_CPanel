<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Thin Eloquent wrapper on the homework_quiz_questions table - that table
 *  is accessed via DB::table() everywhere else (see QuizEngineService),
 *  this model exists only so ProgramClass can get a normal HasMany
 *  relation/count for its own "does this lesson have quiz questions"
 *  check, without needing its own table. */
class QuizQuestion extends Model
{
    protected $table = 'homework_quiz_questions';
    protected $guarded = ['id'];
}
