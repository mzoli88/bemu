<?php

namespace mod\templates\models;

use hws\rmc\Model;

class ModulGroups extends Model
{
    protected $table = 'template_modul_groups';

    public $timestamps = false;

    public function defaultCollection(){
        return $this->toArray();
    }

    protected $fillable = [
		"entity_id",
        "group_id",
        "modul_azon",
    ];

    protected $casts = [
		"id" => "integer",
        "entity_id" => "integer",
        "group_id" => "integer",
        "modul_azon" => "string",
    ];

    protected $validation = [
		"entity_id" => "required|integer|digits_between:1,11",
        "group_id" => "required|integer|digits_between:1,11",
        "modul_azon" => "required|max:100",
    ];

    protected $labels = [
		"id" => "Azonosító",
        "entity_id" => "Entitás azonosító",
        "group_id" => "Szerepkör azonosító",
        "modul_azon" => "Modul azonosító",
    ];

    public function Rel_entity(){
        return $this->belongsTo(AdminEntities::class,"entity_id","id");
    }

    public function Rel_group(){
        return $this->belongsTo(AdminGroups::class,"group_id","id");
    }

}
