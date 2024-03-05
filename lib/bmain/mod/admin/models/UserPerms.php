<?php

namespace mod\admin\models;

use hws\rmc\Model;

class UserPerms extends Model
{
    protected $table = 'admin_user_perms';

    public $timestamps = false;

    public function defaultCollection()
    {
        return $this->toArray();
    }

    public function statisticCollection()
    {
        //Groupperms collection-be vezetjük, sql UNION miatt
        $out = new GroupPerms;
        collect($this->toArray())->each(function($val,$key)use($out){
            $out->{$key} = $val;
        });
        return $out->collect('statistic');
    }

    protected $fillable = [
        "entity_id",
        "user_id",
        "modul_azon",
        "perm",
    ];

    protected $casts = [
        "id" => "integer",
        "entity_id" => "integer",
        "user_id" => "integer",
        "modul_azon" => "string",
        "perm" => "string",
    ];

    protected $validation = [
        "entity_id" => "required|integer|digits_between:1,11",
        "user_id" => "required|integer|digits_between:1,20",
        "modul_azon" => "required|max:100",
        "perm" => "required|max:100",
    ];

    protected $labels = [
        "id" => "Azonosító",
        "entity_id" => "Entitás azonosító",
        "user_id" => "Felhasználó azonosító",
        "modul_azon" => "Modul azonosító",
        "perm" => "Jogosultság",
    ];

    public function Rel_user()
    {
        return $this->belongsTo(Users::class, "user_id", "id");
    }

    public function Rel_entity()
    {
        return $this->belongsTo(Entities::class, "entity_id", "id");
    }

}
