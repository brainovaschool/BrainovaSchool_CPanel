<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAbsentNotification extends BaseModel
{
    use HasFactory;

    // sending_time is a plain varchar holding a hand-encoded JSON map of
    // shift_id => time ("1":"10:00"); it is json_encode()'d on write and
    // json_decode()'d on read in the blade, not Eloquent-cast — an 'array'
    // cast here would double-decode and break that.
}
