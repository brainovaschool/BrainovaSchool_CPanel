<?php

namespace App\Models\Staff;

use App\Models\BaseModel;
use App\Models\WebsiteSetup\Program;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One row per Program a staff member teaches - mirrors
 *  StudentProgramEnrollment for the other side of the same course. */
class StaffProgramEnrollment extends BaseModel
{
    protected $guarded = ['id'];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id', 'id');
    }
}
