<?php

namespace App\Models\WebsiteSetup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrialSlot extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'slot_date' => 'date',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /** Seats still available. */
    public function getRemainingAttribute(): int
    {
        return max(0, (int) $this->capacity - (int) $this->booked_count);
    }

    public function getIsOpenAttribute(): bool
    {
        return $this->status == 1
            && $this->remaining > 0
            && $this->slot_date instanceof \Carbon\Carbon
            && $this->slot_date->startOfDay()->gte(now()->startOfDay());
    }

    /** "10:00 AM – 11:00 AM" or just "10:00 AM". */
    public function getTimeLabelAttribute(): string
    {
        return $this->end_time
            ? trim($this->start_time) . ' – ' . trim($this->end_time)
            : trim((string) $this->start_time);
    }
}
