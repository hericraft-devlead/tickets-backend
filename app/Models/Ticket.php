<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'title',
        'description',
        'student_name',
        'student_email',
        'user_id',
        'category_id',
        'department_id',
        'priority_id',
        'ticket_status_id',
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
        return $this->belongsTo(TicketStatus::class, 'ticket_status_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
}
