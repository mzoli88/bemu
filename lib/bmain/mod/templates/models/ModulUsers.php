<?php

namespace mod\templates\models;

use hws\rmc\Model;

class ModulUsers extends Model
{
    protected $table = 'template_modul_users';

    public $timestamps = false;

    public function defaultCollection(){
        return $this->toArray();
    }

    protected $fillable = [
		"entity_id",
        "user_id",
        "modul_azon",
    ];

    protected $casts = [
		"id" => "integer",
        "entity_id" => "integer",
        "user_id" => "integer",
        "modul_azon" => "string",
    ];

    protected $validation = [
		"entity_id" => "required|integer|digits_between:1,11",
        "user_id" => "required|integer|digits_between:1,20",
        "modul_azon" => "required|max:100",
    ];

    protected $labels = [
		"id" => "Azonosító",
        "entity_id" => "Entitás azonosító",
        "user_id" => "Felhasználó azonosító",
        "modul_azon" => "Modul azonosító",
    ];

    public function Rel_entity(){
        return $this->belongsTo(AdminEntities::class,"entity_id","id");
    }

    public function Rel_user(){
        return $this->belongsTo(AdminUsers::class,"user_id","id");
    }

}
