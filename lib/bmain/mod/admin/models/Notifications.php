<?php

namespace mod\admin\models;
use hws\rmc\Model;

class Notifications extends Model
{
    protected $table = 'notify_notifications';

    public $timestamps = false;

    public function defaultCollection()
    {
        $out = $this->toArray();
        $out['date2'] = $this->humanTiming();
        $out['type'] = $this->findType();
        $out['modul_azon'] = $this->findModul();
        $out['message'] = toHtml($this->message);
        return $out;
    }

    public function findType()
    {
        $messageTypes = config('messageTypes');
        if (
            !array_key_exists($this->modul_azon, $messageTypes) ||
            !array_key_exists($this->type, $messageTypes[$this->modul_azon])
        ) return '';
        return  $messageTypes[$this->modul_azon][$this->type]['name'];
    }

    public function findModul()
    {
        $mods = config('mods');
        if (
            !array_key_exists($this->modul_azon, $mods)
        ) return '';
        return  $mods[$this->modul_azon]['name'];
    }

    public function humanTiming()
    {
        $time = time() - $this->date->timestamp;
        $time = ($time < 1) ? 1 : $time;
        $tokens = array(
            31536000 => 'éve',
            2592000 => 'hónapja',
            604800 => 'hete',
            86400 => 'napja',
            3600 => 'órája',
            60 => 'perce',
            1 => 'másodperce'
        );

        foreach ($tokens as $unit => $text) {
            if ($time < $unit) continue;
            $numberOfUnits = floor($time / $unit);
            return $numberOfUnits . ' ' . $text;
        }
    }

    protected $fillable = [
		"user_id",
        "entity_id",
        "date",
        "modul_azon",
        "type",
        "read",
        "message",
        "message_search",
    ];

    protected $casts = [
		"id" => "integer",
        "user_id" => "integer",
        "entity_id" => "integer",
        "date" => "datetime:Y.m.d. H:i:s",
        "modul_azon" => "string",
        "type" => "string",
        "read" => "string",
        "message" => "string",
        "message_search" => "string",
    ];

    protected $validation = [
		"user_id" => "required|integer|digits_between:1,20",
        "entity_id" => "required|integer|digits_between:1,11",
        "date" => "nullable|date",
        "modul_azon" => "required|max:255",
        "type" => "required|max:255",
        "read" => "nullable|max:1",
        "message" => "nullable",
        "message_search" => "nullable",
    ];

    protected $labels = [
		"id" => "Azonosító",
        "user_id" => "Felhasználó azonosító",
        "entity_id" => "Entitás azonosító",
        "date" => "Dátum",
        "modul_azon" => "Modul azonosító",
        "type" => "Típus",
        "read" => "Olvasott",
        "message" => "Tartalom",
        "message_search" => "Keresés segéd",
    ];

    public function Rel_user()
    {
        return $this->belongsTo(Users::class, "user_id", "id");
    }

	public function Rel_entity(){
        return $this->belongsTo(Entities::class,"entity_id","id");
    }
}
