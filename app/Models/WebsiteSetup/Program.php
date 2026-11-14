<?php

namespace App\Models\WebsiteSetup;

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
