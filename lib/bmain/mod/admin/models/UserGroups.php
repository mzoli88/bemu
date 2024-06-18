<?php

namespace mod\admin\models;

use hws\rmc\Model;

class UserGroups extends Model
{
    protected $table = 'admin_user_groups';

    public $timestamps = false;

    public function defaultCollection()
    {
        return $this->toArray();
    }

    protected $fillable = [
        "user_id",
        "group_id",
    ];

    protected $casts = [
        "id" => "integer",
        "user_id" => "integer",
        "group_id" => "integer",
    ];

    protected $validation = [
        "user_id" => "required|integer|digits_between:1,20",
        "group_id" => "required|integer|digits_between:1,11",
    ];

    protected $labels = [
        "id" => "Azonosító",
        "user_id" => "Felhasználó azonosító",
        "group_id" => "Szerepkör azonosító",
    ];

    public function Rel_group()
    {
        return $this->belongsTo(Groups::class, "group_id", "id");
    }

    public function Rel_user()
    {
        return $this->belongsTo(Users::class, "user_id", "id");
    }

    public function Rel_group_perms()
    {
        return $this->belongsTo(GroupPerms::class, "group_id", "group_id");
    }

}
