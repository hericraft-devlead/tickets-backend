<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',

        'contact_name',
        'contact_email',

        'moodle_user_id',
        'assigned_user_id',

        'department_id',
        'category_id',
        'priority_id',
        'status_id',

        'closed_at',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function priority()
    {
        return $this->belongsTo(Priority::class);
    }

    public function status()
    {
        return $this->belongsTo(TicketStatus::class, 'status_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function moodleUser()
    {
        return $this->belongsTo(MoodleUser::class, 'moodle_user_id', 'moodle_user_id');
    }

    public function isGuest(): bool
    {
        return is_null($this->moodle_user_id);
    }
}

