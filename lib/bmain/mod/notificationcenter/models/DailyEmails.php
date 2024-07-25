<?php

namespace mod\notificationcenter\models;

use hws\rmc\Model;

class DailyEmails extends Model
{
    protected $table = 'notify_daily_emails';

    public $timestamps = false;

    public function defaultCollection(){
        return $this->toArray();
    }

    protected $fillable = [
		"priority",
        "day",
        "to_out",
        "successful",
        "failed",
        "resend",
        "entity_id",
    ];

    protected $casts = [
		"id" => "integer",
        "priority" => "integer",
        "day" => "date:Y.m.d.",
        "to_out" => "integer",
        "successful" => "integer",
        "failed" => "integer",
        "resend" => "integer",
        "entity_id" => "integer",
    ];

    protected $validation = [
		"priority" => "required|integer|digits_between:1,11",
        "day" => "required|date",
        "to_out" => "nullable|integer|digits_between:1,11",
        "successful" => "nullable|integer|digits_between:1,11",
        "failed" => "nullable|integer|digits_between:1,11",
        "resend" => "nullable|integer|digits_between:1,11",
        "entity_id" => "required|integer|digits_between:1,11",
    ];

    protected $labels = [
		"id" => "Azonosító",
        "priority" => "Prioritás",
        "day" => "Nap",
        "to_out" => "Kiküldendő",
        "successful" => "Sikeres",
        "failed" => "Sikertelen",
        "resend" => "Úraküldés",
        "entity_id" => "Entitás",
    ];


	public function Rel_priority(){
        return $this->belongsTo(Priorities::class,"priority","id");
    }
}
