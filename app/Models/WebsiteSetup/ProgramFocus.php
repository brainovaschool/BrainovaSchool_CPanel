<?php

namespace App\Models\WebsiteSetup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProgramFocus extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function category()
    {
        return $this->belongsTo(ProgramCategory::class, 'program_category_id', 'id');
    }

    public function programs()
    {
        return $this->hasMany(Program::class)->orderBy('sort_order')->orderBy('title');
    }

    public function activePrograms()
    {
        return $this->programs()->where('status', 1);
    }
}
