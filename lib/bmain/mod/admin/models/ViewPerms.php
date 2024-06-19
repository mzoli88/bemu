<?php

namespace mod\admin\models;

use hws\rmc\Model;

class ViewPerms extends Model
{
    protected $table = 'admin_view_perms';

    public $timestamps = false;

    public function defaultCollection(){
        return $this->toArray();
    }

    protected $fillable = [
    ];

    protected $casts = [
        "perm_id" => "integer",
        "user_id" => "integer",
        "modul_azon" => "string",
        "perm" => "string",
    ];

    protected $validation = [
    ];

    protected $labels = [
    ];


	public function perm(){
        return $this->belongsTo(Perms::class,"perm_id","id");
    }

	public function users(){
        return $this->hasMany(users::class,"user_id","id");
    }
}
