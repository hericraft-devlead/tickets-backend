<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // Super Admin (sin departamento asignado)
    public function isAdmin(): bool
    {
        return $this->role === 0 && is_null($this->department_id);
    }

    // Jefe de Departamento (admin con departamento asignado)
    public function isDepartmentHead(): bool
    {
        return $this->role === 0 && !is_null($this->department_id);
    }

    // Agente de soporte
    public function isSupportAgent(): bool
    {
        return $this->role === 1;
    }

    public function getRoleName(): string
    {
        if ($this->isAdmin()) {
            return 'Super Administrador';
        } elseif ($this->isDepartmentHead()) {
            return 'Jefe de Departamento';
        } elseif ($this->isSupportAgent()) {
            return 'Agente de Soporte';
        }
        
        return 'Usuario';
    }

    // Para compatibilidad con código existente
    public function getIsAdminAttribute(): bool
    {
        return $this->isAdmin();
    }
}