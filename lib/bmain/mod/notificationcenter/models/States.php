<?php

namespace mod\notificationcenter\models;

use hws\rmc\Model;

class States extends Model
{
    protected $table = 'notify_states';

    public $timestamps = false;

    public function defaultCollection(){
        return $this->toArray();
    }

    protected $fillable = [
		"notify_state",
    ];

    protected $casts = [
		"id" => "integer",
        "notify_state" => "string",
    ];

    protected $validation = [
		"notify_state" => "required|max:100",
    ];

    protected $labels = [
		"id" => "Azonosító",
        "notify_state" => "Állapotok",
    ];

    public function Rel_Emails_state(){
        return $this->hasMany(Emails::class,"state","id");
    }


	public function Rel_Pushs_state(){
        return $this->hasMany(Pushs::class,"state","id");
    }
}
