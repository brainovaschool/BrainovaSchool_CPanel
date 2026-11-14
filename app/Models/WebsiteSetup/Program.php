<?php

namespace App\Models\WebsiteSetup;

use App\Models\Academic\Classes;
use App\Models\Academic\Section;
use App\Models\Academic\Subject;
use App\Models\ClassContent\ClassContentModule;
use App\Models\Staff\Staff;
use App\Models\Upload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Program extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'overview'   => 'array',
        'highlights' => 'array',
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    /** The fixed set of lifecycle stages a program can show as its status
     *  pill - kept to a short list so each one has a predictable colour,
     *  rather than free text like "badge" (a different field - that one's
     *  the subject/provider label, e.g. "Maths" or "CODENOVA"). */
    public const STATUS_LABELS = [
        'ongoing'     => 'Ongoing',
        'upcoming'    => 'Upcoming',
        'coming_soon' => 'Coming Soon',
        'enrolling'   => 'Enrolling',
    ];

    public function getStatusLabelTextAttribute(): ?string
    {
        return self::STATUS_LABELS[$this->status_label] ?? null;
    }

    /** 1/2/3, set from the Program edit form's "Level" field. */
    public const SKILL_LEVELS = [
        1 => 'Beginner',
        2 => 'Intermediate',
        3 => 'Advance',
    ];

    public function getSkillLevelTextAttribute(): ?string
    {
        return self::SKILL_LEVELS[$this->skill_level] ?? null;
    }

    public function category()
    {
        return $this->belongsTo(ProgramCategory::class, 'program_category_id', 'id');
    }

    public function focus()
    {
        return $this->belongsTo(ProgramFocus::class, 'program_focus_id', 'id');
    }

    public function upload()
    {
        return $this->belongsTo(Upload::class, 'upload_id', 'id');
    }

    /** Which staff teach this program - set from the Program edit form. */
    public function teachers()
    {
        return $this->belongsToMany(Staff::class, 'staff_program_enrollments', 'program_id', 'staff_id');
    }

    /** Optional - the real academic Class/Section/Subject this program is
     *  taught under, set from the Program edit form's "Link to Class
     *  Content" fields. Null until someone sets it; the Course Details
     *  block shows a "not linked yet" state for any program without one. */
    public function classes()
    {
        return $this->belongsTo(Classes::class, 'classes_id', 'id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id', 'id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'id');
    }

    public function isLinkedToClassContent(): bool
    {
        return (bool) ($this->classes_id && $this->section_id && $this->subject_id);
    }

    /** Approved modules (with their lessons) for this program's linked
     *  Class/Section/Subject - what Course Details actually renders. Empty
     *  if the program isn't linked yet. */
    public function contentModules()
    {
        if (!$this->isLinkedToClassContent()) {
            return collect();
        }

        return ClassContentModule::approved()
            ->where('classes_id', $this->classes_id)
            ->where('section_id', $this->section_id)
            ->where('subject_id', $this->subject_id)
            ->with(['lessons.materials', 'lessons.activities', 'lessons.outcomes', 'lessons.homeworks'])
            ->orderBy('sort_order')
            ->get();
    }

    /** The newer, simpler way to add a course's classes (materials, PPTs,
     *  activities, outcomes, project, quiz) - a teacher fills these in
     *  directly against the Program, no Class/Section/Subject link
     *  required, unlike Class Content. */
    public function programClasses()
    {
        return $this->hasMany(ProgramClass::class, 'program_id')->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /** Resolved image path: uploaded file first, then external URL. */
    public function getImageAttribute()
    {
        if ($this->upload && $this->upload->path) {
            return globalAsset($this->upload->path, '1200X800.webp');
        }

        return $this->image_url ?: null;
    }
}
