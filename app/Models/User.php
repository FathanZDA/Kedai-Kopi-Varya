<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Route awal setelah login, ditentukan dari role akun.
     */
    public function routeBeranda(): string
    {
        return match ($this->role) {
            UserRole::Admin => route('admin.dashboard'),
            UserRole::Kasir => route('kasir.pesanan.baru'),
        };
    }

    /**
     * Inisial nama untuk avatar header/sidebar.
     */
    public function inisial(): string
    {
        $bagian = preg_split('/\s+/', trim($this->name)) ?: [];

        return collect(array_slice($bagian, 0, 2))
            ->map(fn (string $kata): string => mb_strtoupper(mb_substr($kata, 0, 1)))
            ->implode('');
    }

    /**
     * Nama depan untuk sapaan singkat area kasir.
     */
    public function namaDepan(): string
    {
        return explode(' ', trim($this->name))[0] ?: $this->name;
    }
}
