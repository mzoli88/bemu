<?php

namespace mod\notificationcenter\models;

use Illuminate\Support\Facades\Cache;
use hws\rmc\Model;

class Priorities extends Model
{
    protected $table = 'notify_priorities';

    public $timestamps = false;

    public function defaultCollection(){
        return $this->toArray();
    }

    protected $fillable = [
		"priority",
        "label",
    ];

    protected $casts = [
		"id" => "integer",
        "priority" => "string",
        "label" => "string",
    ];

    protected $validation = [
		"priority" => "required|max:255",
        "label" => "required|max:255",
    ];

    protected $labels = [
		"id" => "Azonosító",
        "priority" => "Proiritás",
        "label" => "Cimke",
    ];

    public function Rel_Emails_priority(){
        return $this->hasMany(Emails::class,"priority","id");
    }

    static function cache()
    {
        $_priorities = self::get()->mapWithKeys(function ($r) {
            return [$r->id => $r->priority];
        })->toArray();
        Cache::put('notify_priorities', $_priorities);
        return $_priorities;
    }

    static function getCache()
    {
        return Cache::get('notify_priorities', self::cache());
    }

	public function Rel_Pushs_priority(){
        return $this->hasMany(Pushs::class,"priority","id");
    }

	public function Rel_DailyEmails_priority(){
        return $this->hasMany(DailyEmails::class,"priority","id");
    }

	public function Rel_DailyPushs_priority(){
        return $this->hasMany(DailyPushs::class,"priority","id");
    }
}
