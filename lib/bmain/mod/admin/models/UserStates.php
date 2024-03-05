<?php

namespace mod\admin\models;

use hws\rmc\Model;

class UserStates extends Model
{
    protected $table = 'admin_user_states';

    public $timestamps = false;

    public function defaultCollection(){
        return $this->toArray();
    }

    protected $fillable = [
        "state",
    ];

    protected $casts = [
        "id" => "integer",
        "state" => "string",
    ];

    protected $validation = [
        "state" => "required|max:255",
    ];

    protected $labels = [
        "id" => "Azonosító",
        "state" => "Státusz",
    ];

    public function Rel_Users_state(){
        return $this->hasMany(Users::class,"state_id","id");
    }

}
