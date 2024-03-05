<?php

namespace mod\admin\models;

use hws\rmc\Model;

class Documents extends Model
{
    protected $table = 'admin_documents';

    public $timestamps = false;

    public function defaultCollection(){
        $out = $this->toArray();
        $mods = config('mods');
        if(array_key_exists($this->modul_azon,$mods)) $out['modul'] = $mods[$this->modul_azon]['name'];
        return $out;
    }

    protected $fillable = [
		"name",
        "modul_azon",
        //"file_name",
    ];

    protected $casts = [
		"id" => "integer",
        "name" => "string",
        "modul_azon" => "string",
        "file_name" => "string",
    ];

    protected $validation = [
		"name" => "required|max:255",
        "modul_azon" => "required|max:100",
        "file_name" => "required|max:255",
    ];

    protected $labels = [
		"id" => "Azonosító",
        "name" => "Megnevezés",
        "modul_azon" => "Modul",
        "file_name" => "Fájl név",
    ];


}
