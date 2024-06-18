<?php

namespace mod\admin\models;

use hws\rmc\Model;

class UserEntities extends Model
{
    protected $table = 'admin_user_entities';

    public $timestamps = false;

    public function defaultCollection(){
        return $this->toArray();
    }

    protected $fillable = [
		"entity_id",
        "user_id",
    ];

    protected $casts = [
		"id" => "integer",
        "entity_id" => "integer",
        "user_id" => "integer",
    ];

    protected $validation = [
		"entity_id" => "required|integer|digits_between:1,11",
        "user_id" => "required|integer|digits_between:1,20",
    ];

    protected $labels = [
		"id" => "Azonosító",
        "entity_id" => "Entitás azonosító",
        "user_id" => "Felhasználó azonosító",
    ];

    public function Rel_user(){
        return $this->belongsTo(Users::class,"user_id","id");
    }

    public function Rel_entity(){
        return $this->belongsTo(Entities::class,"entity_id","id");
    }

}
