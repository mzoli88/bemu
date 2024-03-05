<?php

namespace mod\admin\models;

use hws\rmc\Model;

class Groups extends Model
{
    protected $table = 'admin_groups';

    static $useStatus = true;
    static $showAll = false;

    public $timestamps = false;

    public function defaultCollection()
    {
        return $this->toArray();
    }

    public function newQuery()
    {
        if (static::$showAll == true) return parent::newQuery();
        return parent::newQuery()->where('admin_groups.status','I')->entity();
    }

    protected $fillable = [
		"entity_id",
        "name",
        "status",
    ];

    protected $casts = [
		"id" => "integer",
        "entity_id" => "integer",
        "name" => "string",
        "status" => "string",
    ];

    protected $validation = [
		"entity_id" => "required|integer|digits_between:1,11",
        "name" => "required|max:255",
        "status" => "required|max:1",
    ];

    protected $labels = [
		"id" => "Azonosító",
        "name" => "Szerepkör",
        "status" => "Státusz",
    ];

    public function Rel_entity()
    {
        return $this->belongsTo(Entities::class, "entity_id", "id");
    }

    public function Rel_GroupPerms_group()
    {
        return $this->hasMany(GroupPerms::class, "group_id", "id");
    }

    public function Rel_UserGroup_group()
    {
        return $this->hasMany(UserGroup::class, "group_id", "id");
    }


    public function Rel_UserGroups_group()
    {
        return $this->hasMany(UserGroups::class, "group_id", "id");
    }
}
