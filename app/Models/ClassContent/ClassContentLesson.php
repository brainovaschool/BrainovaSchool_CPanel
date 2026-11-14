<?php

namespace App\Models\ClassContent;

use App\Models\BaseModel;
use App\Models\Homework;
use App\Traits\ResolvesVideoEmbed;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassContentLesson extends BaseModel
{
    use ResolvesVideoEmbed;

    protected $guarded = ['id'];

    public function module(): BelongsTo
    {
        return $this->belongsTo(ClassContentModule::class, 'module_id');
    }

    /** Homework tasks (quiz/assignment/project/etc.) linked to this lesson
     *  via the Create/Edit Homework form's optional "Link to a class"
     *  dropdown - see Homework::lesson(). */
    public function homeworks(): HasMany
    {
        return $this->hasMany(Homework::class, 'class_content_lesson_id')->orderBy('date');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ClassContentMaterial::class, 'lesson_id')->orderBy('sort_order');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ClassContentActivity::class, 'lesson_id')->orderBy('sort_order');
    }

    public function outcomes(): HasMany
    {
        return $this->hasMany(ClassContentOutcome::class, 'lesson_id')->orderBy('sort_order');
    }

    public function hasProject(): bool
    {
        return (bool) $this->project_title;
    }

    public function hasQuiz(): bool
    {
        return (bool) $this->quiz_title;
    }

    /** $map is a student's completions, keyed "type:id" (see
     *  ClassContentCompletionRepository::mapForStudent()) - plain data, no
     *  query here, so this is just as usable from a Blade view as from a
     *  repository. A lesson is done once there's nothing left under it:
     *  itself viewed, every activity done, and its project/quiz done if it
     *  has one. */
    public function isDoneFor(array $map): bool
    {
        if (!isset($map[ClassContentCompletion::LESSON . ':' . $this->id])) {
            return false;
        }
        foreach ($this->activities as $activity) {
            if (!isset($map[ClassContentCompletion::ACTIVITY . ':' . $activity->id])) {
                return false;
            }
        }
        if ($this->hasProject() && !isset($map[ClassContentCompletion::PROJECT . ':' . $this->id])) {
            return false;
        }
        if ($this->hasQuiz() && !isset($map[ClassContentCompletion::QUIZ . ':' . $this->id])) {
            return false;
        }
        return true;
    }
}
