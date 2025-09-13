<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios'; // Nombre exacto de la tabla

    protected $fillable = [
        'nombre',
        'email',
        'password',
        'rol',
    ];

    // Ocultar el password al devolver el modelo en JSON
    protected $hidden = [
        'password',
    ];

    // Configurar casting para password
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Especificar el campo de email para autenticacion
    public function getAuthIdentifierName()
    {
        return 'email';
    }

    // Especificar el campo de password para autenticacion
    public function getAuthPassword()
    {
        return $this->password;
    }
}
