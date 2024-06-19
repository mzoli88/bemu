<?php

namespace mod\admin\models;

use hws\rmc\Model;

class GroupPerms extends Model
{
    protected $table = 'admin_group_perms';

    public $timestamps = false;

    public function defaultCollection()
    {
        return $this->toArray();
    }

    // public function statisticCollection()
    // {
    //     $out = $this->toArray();
    //     $out['modul_name'] = getModulName($this->modul_azon);
    //     $out['perm_name'] = getPermissionName($this->perm, $this->modul_azon);
    //     $out['perm_type'] = getPermissionType($this->perm, $this->modul_azon);
    //     $out['perm_origin'] = UserPerms::entity()
    //         ->where('modul_azon', $this->modul_azon)
    //         ->where('perm', $this->perm)
    //         ->where('user_id', $this->user_id)
    //         ->one() ? 'Közvetlen' : 'Közvetett';

    //     $out['group_name'] = GroupPerms::entity()
    //         ->select([
    //             "modul_azon",
    //             "perm",
    //             "user_id" => "Rel_user_group.user_id",
    //             "name" => "Rel_group.name",
    //         ])
    //         ->orderBy('name')
    //         ->where('modul_azon', $this->modul_azon)
    //         ->where('perm', $this->perm)
    //         ->where('user_id', $this->user_id)
    //         ->get()->pluck('name')->implode(', ');

    //     return $out;
    // }


    public function statistic2Collection()
    {
        $out = $this->toArray();
        $this->statistic2($out);
        return $out;
    }

    public function statistic2exportCollection()
    {
        $out = $this->toArray();
        $this->statistic2($out,', ');
        return $out;
    }

    // public function statistic2(&$out,$separator = "<br>")
    // {
    //     $out['modul_name'] = getModulName($this->modul_azon);
    //     $out['perm_name'] = getPermissionName($this->perm, $this->modul_azon);
    //     $out['perm_type'] = getPermissionType($this->perm, $this->modul_azon);


    //     $UserPerms = UserPerms::entity()
    //         ->select([
    //             "name" => "Rel_user.name",
    //             "login" => "Rel_user.login",
    //         ])
    //         ->whereIn('Rel_user.state_id', [1, 2, 5, 6, 7])
    //         ->where('modul_azon', $this->modul_azon)
    //         ->groupBy('user_id')
    //         ->where('perm', $this->perm);


    //     $groupUserPerms = GroupPerms::entity()
    //         ->select([
    //             "name" => "Rel_user_group.Rel_user.name",
    //             "login" => "Rel_user_group.Rel_user.login",
    //         ])
    //         ->where('modul_azon', $this->modul_azon)
    //         ->where('perm', $this->perm)
    //         ->join('Rel_user_group')
    //         ->groupBy('user_id')
    //         ->whereIn('state_id', [1, 2, 5, 6, 7]);

    //     $groupUserPerms->union($UserPerms);

    //     $out['user_name'] = $groupUserPerms->get()->sortBy('name')->map(function ($val) {
    //         return $val->name . ' - ' . $val->login;
    //     })->implode($separator);
    // }

    protected $fillable = [
        "entity_id",
        "group_id",
        "modul_azon",
        "perm",
    ];

    protected $casts = [
        "id" => "integer",
        "entity_id" => "integer",
        "group_id" => "integer",
        "modul_azon" => "string",
        "perm" => "string",
    ];

    protected $validation = [
        "entity_id" => "required|integer|digits_between:1,11",
        "group_id" => "required|integer|digits_between:1,11",
        "modul_azon" => "required|max:100",
        "perm" => "required|max:100",
    ];

    protected $labels = [
        "id" => "Azonosító",
        "entity_id" => "Entitás azonosító",
        "group_id" => "Szerepkör azonosító",
        "modul_azon" => "Modul azonosító",
        "perm" => "Jogosultság",
    ];

    public function Rel_group()
    {
        return $this->belongsTo(Groups::class, "group_id", "id");
    }

    public function Rel_entity()
    {
        return $this->belongsTo(Entities::class, "entity_id", "id");
    }

    public function Rel_user_group()
    {
        return $this->hasMany(UserGroups::class, "group_id", "group_id");
    }
}
