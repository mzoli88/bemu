<?php

namespace mod\notificationcenter\models;

use hws\rmc\Model;
use mod\admin\models\Entities;

class QueuesCount extends Model
{
    protected $table = 'notify_queues_count';

    public $timestamps = false;

    public function defaultCollection(){
        return $this->toArray();
    }

    protected $fillable = [
		"entity_id",
        "priority",
        "type",
        "count",
    ];

    protected $casts = [
		"id" => "integer",
        "entity_id" => "integer",
        "priority" => "integer",
        "type" => "string",
        "count" => "integer",
    ];

    protected $validation = [
		"entity_id" => "required|integer|digits_between:1,11",
        "priority" => "required|integer|digits_between:1,11",
        "type" => "required|max:255",
        "count" => "nullable|integer|digits_between:1,11",
    ];

    protected $labels = [
		"id" => "Azonosító",
        "entity_id" => "Entitás azonosító",
        "priority" => "Prioritás",
        "type" => "Típus",
        "count" => "Darabszám",
    ];

    public function Rel_entity(){
        return $this->belongsTo(Entities::class,"entity_id","id");
    }

    public function Rel_priority(){
        return $this->belongsTo(Priorities::class,"priority","id");
    }

}
