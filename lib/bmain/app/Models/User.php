<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use mod\admin\models\UserEntities;

class User extends Authenticatable
{
    protected $table = 'nevek';

    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
        'cookie_token',
        'old_passwords',
        'activation_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */

    protected $casts = [
        "id" => "integer",
        "name" => "string",
        "email" => "string",
        "email_verified_at" => "datetime:Y.m.d. H:i:s",
        "password" => "string",
        "remember_token" => "string",
        "sys_admin" => "string",
        "api_token" => "string",
        "created_at" => "datetime:Y.m.d. H:i:s",
        "updated_at" => "datetime:Y.m.d. H:i:s",
        "last_login" => "datetime:Y.m.d. H:i:s",
        "cookie_token" => "string",
        "login" => "string",
        "state" => "integer",
    ];

    public function Rel_UserEntities_user()
    {
        return $this->hasMany(UserEntities::class, "user_id", "id");
    }
}
