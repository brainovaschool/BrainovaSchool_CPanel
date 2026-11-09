<?php

namespace App\Models\ClassContent;

use App\Models\Academic\Classes;
use App\Models\Academic\Section;
use App\Models\Academic\Subject;
use App\Models\BaseModel;
use App\Models\Session;
use App\Models\Staff\Staff;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassContentUnit extends BaseModel
{
    protected $table = 'class_content_units';
    protected $guarded = ['id'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'classes_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(ClassContentModule::class, 'unit_id')->orderBy('sort_order');
    }
}
