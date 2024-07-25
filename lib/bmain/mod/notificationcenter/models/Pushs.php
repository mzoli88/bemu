<?php

namespace mod\notificationcenter\models;

use hws\rmc\Model;

use mod\admin\models\Entities as AdminEntities;

class Pushs extends Model
{
    protected $table = 'notify_pushs';

    public $timestamps = true;

    public function defaultCollection(){
        return $this->toArray();
    }

    public function listCollection()
    {
        $out = $this->toArray();
        $out['modul_id'] = getModulName($out['modul_id']);
        $out['message'] = toHtml($this->message);
        return $out;
    }

    protected $fillable = [
		"entity_id",
        "priority",
        "state",
        "title",
        "body",
        "badge",
        "icon",
        "click_action",
        "sound",
        "tag",
        "sender_params",
        "sent_error",
        "modul_id",
        "token",
        "fmc_result_json",
        "modul_record_id",
        "sent_date",
        "callback",
        "can_be_deleted"
    ];

    protected $casts = [
		"id" => "integer",
        "entity_id" => "integer",
        "priority" => "integer",
        "state" => "integer",
        "title" => "string",
        "body" => "string",
        "badge" => "string",
        "icon" => "string",
        "click_action" => "string",
        "sound" => "string",
        "tag" => "string",
        "sender_params" => "string",
        "sent_error" => "string",
        "modul_id" => "string",
        "token" => "string",
        "fmc_result_json" => "string",
        "modul_record_id" => "integer",
        "created_at" => "datetime:Y.m.d. H:i:s",
        "updated_at" => "datetime:Y.m.d. H:i:s",
        "sent_date" => "datetime:Y.m.d. H:i:s",
        "callback" => "string",
        "can_be_deleted" => "string"
    ];

    protected $validation = [
		"entity_id" => "required|integer|digits_between:1,11",
        "priority" => "required|integer|digits_between:1,11",
        "state" => "required|integer|digits_between:1,11",
        "title" => "required|max:255",
        "body" => "required",
        "badge" => "nullable|max:255",
        "icon" => "nullable|max:255",
        "click_action" => "nullable|max:255",
        "sound" => "nullable|max:255",
        "tag" => "nullable|max:255",
        "sender_params" => "required",
        "sent_error" => "nullable",
        "modul_id" => "required|max:255",
        "token" => "required",
        "fmc_result_json" => "nullable",
        "modul_record_id" => "nullable|integer|digits_between:1,11",
        "sent_date" => "nullable|date",
        "callback" => "nullable|max:255",
        "can_be_deleted" => "nullable|max:1"
    ];

    protected $labels = [
		"id" => "Azonosító",
        "entity_id" => "Entitás azonosító",
        "priority" => "Prioritás",
        "state" => "Állapot",
        "title" => "Címe",
        "body" => "Tartalma",
        "badge" => "Csomag",
        "icon" => "Ikon",
        "click_action" => "Kattintás akció",
        "sound" => "zene",
        "tag" => "tag",
        "sender_params" => "Push továbbítás paraméterei",
        "sent_error" => "Továbbítás hiba",
        "modul_id" => "Modul azonosító",
        "token" => "Címzett token",
        "fmc_result_json" => "FCM válasza",
        "modul_record_id" => "Küldő modul azonosító",
        "created_at" => "Létrehozás ideje",
        "updated_at" => "Módosítás dátum",
        "sent_date" => "Továbbítás ideje",
        "callback" => "Válasz visszaadása",
        "can_be_deleted" => "Törölhető"
    ];

    public function Rel_entity(){
        return $this->belongsTo(AdminEntities::class,"entity_id","id");
    }

    public function Rel_priority(){
        return $this->belongsTo(Priorities::class,"priority","id");
    }

    public function Rel_state(){
        return $this->belongsTo(States::class,"state","id");
    }

}
