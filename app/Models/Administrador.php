<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Administrador extends Authenticatable implements FilamentUser, HasName
{
    use Notifiable;

    protected $table = 'administradores'; 

    protected $fillable = ['nombre', 'email', 'password'];

    public function getFilamentName(): string
    {
        return $this->nombre ?? $this->email;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true; 
    }
}