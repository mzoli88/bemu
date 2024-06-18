<?php

namespace mod\admin\models;

use hws\rmc\Model;
use Illuminate\Support\Facades\DB;

class Groups extends Model
{
    protected $table = 'admin_groups';

    static $latin = true;

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
        return parent::newQuery()->where('admin_groups.status', 'I')->entity();
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

    public function Rel_UserGroups_group()
    {
        return $this->hasMany(UserGroups::class, "group_id", "id");
    }

    //->byUser(1)
    //->byUser([1,2])
    public function scopeByUser($query, $user_id = false)
    {
        if (!config('isBorder')) $query->entity();
        if ($user_id) {
            if ($user_id) {
                $query->whereExists(function ($query) use ($user_id) {
                    $query->select(DB::raw(1))
                        ->from('admin_user_groups')
                        ->whereColumn('admin_groups.id', 'admin_user_groups.group_id');
                    if (is_array($user_id)) {
                        $query->whereIn('admin_user_groups.user_id', $user_id);
                    } else {
                        $query->where('admin_user_groups.user_id', $user_id);
                    }
                });
            }
        }
        return $query;
    }

    //->byModul()
    public function scopeByModul($query, $modul_azon = null)
    {
        if (!config('isBorder')) return $query;

        $modul_azon = $modul_azon ?? getModulAzon();

        $query->whereExists(function ($query) use ($modul_azon) {
            $query->select(DB::raw(1))
                ->from('b_nagycsoport_modul')
                ->whereColumn('admin_groups.id', 'b_nagycsoport_modul.b_nagycsoport_id')
                ->where('b_nagycsoport_modul.modul_azon', $modul_azon);
        });

        return $query;
    }
}
