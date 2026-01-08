<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use App\Models\Category;
use App\Models\Priority;
use App\Models\TicketStatus;
use App\Models\Tag;
use App\Models\MoodleUser;
use App\Models\User;

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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'status_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'tag_ticket');
    }

    public function moodleUser(): BelongsTo
    {
        return $this->belongsTo(MoodleUser::class, 'moodle_user_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function isGuest(): bool
    {
        return is_null($this->moodle_user_id);
    }
}