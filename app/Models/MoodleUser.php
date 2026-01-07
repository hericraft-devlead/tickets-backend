<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class MoodleUser extends Model
{
    use HasApiTokens, HasFactory;

    public $incrementing = false;
    
    protected $keyType = 'int';

    protected $primaryKey = 'moodle_user_id';

    protected $fillable = [
        'moodle_user_id',
        'username',
        'name',
        'email',
        'firstname',
        'lastname',
    ];
    
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'moodle_user_id', 'moodle_user_id');
    }
}