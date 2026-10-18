<?php

namespace App\Models;

use App\Models\Role;
use App\Models\Upload;
use App\Models\Staff\Staff;
use App\Models\Staff\Designation;
use App\Models\StudentInfo\Student;
use Laravel\Sanctum\HasApiTokens;
use Modules\LiveChat\Entities\Message;
use Illuminate\Notifications\Notifiable;
use App\Models\StudentInfo\ParentGuardian;
use Modules\VehicleTracker\Entities\Driver;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;


class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'image',
        'password',
        'date_of_birth',
        'upload_id',
        'email_verified_at',
        'phone',
        'permission',
        'last_login',
        'designation_id',
        'status',
        'reset_password_otp',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'reset_password_otp',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'permissions'       => 'array'
    ];


    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class, 'upload_id', 'id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function userGender()
    {
        return $this->belongsTo(Gender::class, 'gender', 'id');
    }

    public function student()
    {
        return $this->hasOne(Student::class, 'user_id', 'id');
    }

    public function staff()
    {
        return $this->hasOne(Staff::class, 'user_id');
    }

    public function parent()
    {
        return $this->hasOne(ParentGuardian::class, 'user_id', 'id');
    }

    public function driver()
    {
        return $this->hasOne(Driver::class, 'user_id', 'id');
    }


    public function unreadNotifications()
    {
        return $this->hasMany(SystemNotification::class, 'reciver_id', 'id')->latest()->where('is_read', 0)->select('id', 'title', 'message', 'reciver_id', 'created_at');
    }

    /** Same list as unreadNotifications(), but read ones stay too instead
     *  of vanishing the moment they're opened — the bell/dashboard list
     *  tells read from unread by styling (is_read), not by disappearing. */
    public function recentNotifications()
    {
        return $this->hasMany(SystemNotification::class, 'reciver_id', 'id')->latest()->limit(20)->select('id', 'title', 'message', 'url', 'reciver_id', 'is_read', 'created_at');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class, 'receiver_id')->latest();
    }

    public function unreadMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id')->where('is_seen', 0);
    }


    public function notification_subscribe_channel()
    {
        $notification_channels = [
            'user' . $this->id,
        ];

        return $notification_channels;
    }
}
