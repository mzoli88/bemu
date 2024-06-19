<?php

namespace mod\admin\models;

use hws\rmc\Model;

class Perms extends Model
{
    protected $table = 'admin_perms';

    public $timestamps = false;

    public function defaultCollection(){
        return $this->toArray();
    }

    protected $fillable = [
        "name",
        "modul_azon",
        "perm",
        "is_menu",
    ];

    protected $casts = [
        "id" => "integer",
        "name" => "string",
        "modul_azon" => "string",
        "perm" => "string",
        "is_menu" => "string",
    ];

    protected $validation = [
        "name" => "required|max:255",
        "modul_azon" => "required|max:255",
        "perm" => "required|max:255",
        "is_menu" => "required|max:1",
    ];

    protected $labels = [
        "id" => "Azonosító",
        "name" => "Jogosultság",
        "modul_azon" => "Modul azonosító",
        "perm" => "Jogosultság azonosító",
        "is_menu" => "Menüpont-e?",
    ];


	public function Rel_GroupPerms_perm(){
        return $this->hasMany(GroupPerms::class,"perm_id","id");
    }

	public function Rel_UserPerms_perm(){
        return $this->hasMany(UserPerms::class,"perm_id","id");
    }
}
