<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens, SoftDeletes, HasFactory;

    const ROLE_ADMIN = 1;
    const ROLE_GENERAL = 0;

    const STATUS_DISABLE = 0; // 停用
    const STATUS_ENABLE = 1; // 啟用中
    const STATUS_WAITING = 2; // 等待開通

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function namespaces()
    {
        return $this->hasMany(MyNamespace::class);
    }

    public function folders()
    {
        return $this->hasMany(FavoriteFolder::class);
    }
}
