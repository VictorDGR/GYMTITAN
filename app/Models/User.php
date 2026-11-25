<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public const ROLES = ['administrador', 'recepcionista', 'cliente'];

    protected $fillable = ['name', 'email', 'ci', 'password', 'rol'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class);
    }

    public function esAdministrador(): bool
    {
        return $this->rol === 'administrador';
    }

    public function esRecepcionista(): bool
    {
        return $this->rol === 'recepcionista';
    }

    public function esCliente(): bool
    {
        return $this->rol === 'cliente';
    }

    public function iniciales(): string
    {
        $partes = preg_split('/\s+/', trim($this->name));

        return mb_strtoupper(mb_substr($partes[0] ?? '', 0, 1).mb_substr($partes[1] ?? '', 0, 1));
    }
}
