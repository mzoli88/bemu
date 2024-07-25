<?php

namespace mod\notificationcenter\models;

use hws\rmc\Model;

class Jobs extends Model
{
    protected $table = 'jobs';

    public $timestamps = false;

    public function defaultCollection(){
        return $this->toArray();
    }

    protected $fillable = [
		"queue",
        "payload",
        "attempts",
        "reserved_at",
        "available_at",
    ];

    protected $casts = [
		"id" => "integer",
        "queue" => "string",
        "payload" => "string",
        "attempts" => "boolean",
        "reserved_at" => "integer",
        "available_at" => "integer",
        "created_at" => "integer",
    ];

    protected $validation = [
		"queue" => "required|max:255",
        "payload" => "required",
        "attempts" => "required",
        "reserved_at" => "nullable|integer|digits_between:1,11",
        "available_at" => "required|integer|digits_between:1,11",
    ];

    protected $labels = [
		"id" => "Azonosító",
        "created_at" => "Létrehozás dátum",
    ];

}
